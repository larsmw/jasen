<?php
include_once('include/logger.php');

class Crawler extends Component {
  private $log;
  private $robots;
  private $d;

  public function __construct() {
    $this->log = new Logger();
    $this->d = new Database();
    $this->register('core', 'cron', array($this, "cron"));
    $this->register('core', 'render', array($this, "render"));

    if (!$this->d->tableExists("crawl_queue")) {
      $sql = "CREATE TABLE crawl_queue ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
           "url_id INT NOT NULL, time_to_crawl TIMESTAMP);";
      $this->d->exec($sql);
    }
    if (!$this->d->tableExists("crawl_uri")) {
      $sql = "CREATE TABLE crawl_uri ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " . 
	"path VARCHAR(4096), domain_id INT, scheme VARCHAR(12));";
      $this->d->exec($sql);
    }
    if (!$this->d->tableExists("crawl_domain")) {
      $sql = "CREATE TABLE crawl_domain ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " . 
	"name VARCHAR(4096));";
      $this->d->exec($sql);
    }

    /*
crawl_stat,
url_id, did, time.


*/
    if (!$this->d->tableExists("crawl_queue")) {
      $sql = "CREATE TABLE crawl_stat ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
           "url_id INT NOT NULL, domain_id INT, time_crawled TIMESTAMP);";
      $this->d->exec($sql);
    }


  }

  public function render($r, $e, $p) {

    $this->robots = new RobotsTxt("http://www.lokalkalender.dk/");
    //var_dump($this->robots->isBlocked("/includes/test"));

    $t = new Template("templates/user.tpl");
    $t->set("user_name", "jeg mangler en template-fil");
    $t->set("css_class", 'user');
    $this->process();
    return array('sidebar' => $t->output());
  }

  public function cron() {
    $this->log->add("crawler starter");
    //$this->process();
  }

  private function process() {
    $arr_http_code_ok = array(200);

    foreach($this->getUriList() as $k=>$a_uri) {
      //var_dump($a_uri);
      $uri = $a_uri['url'];
      //var_dump($uri);
      $this->msg("Crawling : " . $uri);
      $uri_parts = parse_url($uri);
      if(empty($uri_parts['path'])) $uri_parts['path'] = "/";
      $this->log->add(var_export($uri_parts, TRUE));
      $this->robots = new RobotsTxt($uri_parts['scheme']."://" . $uri_parts['host']);
      if (!$this->robots->isBlocked($uri_parts['path'])) {
          //$this->msg("url id: " . $this->getUrlID($uri));
          $this->log->add("I Will crawl : " . var_export($uri_parts, TRUE) . var_export($uri, TRUE));
          $response = $this->fetch($uri);
          /*
            response : array (
            \'url\' => \'http://the.url.i/crawled/\',
            \'content_type\' => \'text/html;charset=UTF-8\',
            \'http_code\' => 200,
            \'header_size\' => 292,
            \'request_size\' => 238,
            \'filetime\' => -1,
            \'ssl_verify_result\' => 0,
            \'redirect_count\' => 0,
            \'total_time\' => 0.40413500000000002,
            \'namelookup_time\' => 0.028840999999999999,
            \'connect_time\' => 0.14061000000000001,
            \'pretransfer_time\' => 0.140766,
            \'size_upload\' => 0,
            \'size_download\' => 9854,
            \'speed_download\' => 24382,
            \'speed_upload\' => 0,
            \'download_content_length\' => 9854,
            \'upload_content_length\' => 0,
            \'starttransfer_time\' => 0.39576500000000003,
            \'redirect_time\' => 0,
            \'redirect_url\' => \'\',
            \'primary_ip\' => \'1.2.3.4\',
            \'certinfo\' => 
            array (
            ),
            \'primary_port\' => 80,
            \'local_ip\' => \'10.0.2.15\',
            \'local_port\' => 49796,
            \'errno\' => 0,
            \'errmsg\' => \'\',
            \'content\' => \' Content of webpage.
          */
          //$this->msg("response : " . var_export($response, TRUE));
          if (in_array($response['http_code'], $arr_http_code_ok)) {
              $this->msg("response : total_time=" . 
                         var_export($response['total_time'], TRUE)."s.");
              
              $DOM = new DOMDocument();
              //load the html string into the DOMDocument
              $DOM->loadHTML($response['content']);
              //get a list of all <A> tags
              $a = $DOM->getElementsByTagName('a');
              //loop through all <A> tags
              foreach($a as $link){
                  $new_url = parse_url($link->getAttribute('href'));
                  if (empty($new_url['host'])) $new_url['host'] = $uri_parts['host'];
                  if (empty($new_url['scheme'])) $new_url['scheme'] = $uri_parts['scheme'];
                  if (empty($new_url['path'])) $new_url['path'] = "";
                  //$this->msg("link-text : " . var_export($link->nodeValue, TRUE));
                  //$this->msg("link : " . var_export($new_url, TRUE));
                  $n = $new_url['scheme']."://".$new_url['host'].$new_url['path'];
                  $crawl_url = $this->getUrlID($n);
                  
                  $sql = "SELECT id FROM crawl_queue WHERE 'url_id' = '$crawl_url';";
                  $r = $this->d->q($sql);
                  if( !is_array($r) || count($r)===0 ) {
                      $sql = "INSERT INTO crawl_queue (url_id) VALUES ($crawl_url)";
                      $q = $this->d->exec($sql);
                      $this->msg("Added " . $n . " to queue.");
                  }
                  else {
                      $this->msg("Did not add '" . $n . "' to queue.");
                  }
              }
          }
      }
    }
  }

  private function getUriList($count = 1) {
    $r = $this->d->q("select concat(u.scheme,'://',d.name,u.path) as url, q.id from crawl_queue q join crawl_uri u on q.url_id=u.id join crawl_domain d on u.domain_id=d.id limit " . $count . ";");
    //var_dump($r);
    if (empty($r)) {
      return array("https://en.wikipedia.org/wiki/Main_Page");
    } else {
        $d = $this->d->q("delete from crawl_queue where id=" . $r[0]['id'] . ";");
      return $r;
    }
  }

  private function getUrlID($url) {
    $tmp = parse_url($url);
    if(empty($tmp['host'])) throw new Exception("missing host");//$tmp['host'] = $base['host'];
    //$this->msg($url);
    //$tmp['scheme'] = $this->getSchemeID($tmp['scheme']);
    $tmp['host'] = $this->getDomainID($tmp['host']);
    if(!isset($tmp['path'])) $tmp['path'] = '/';
    $ret[] = $tmp;
    
    $r = $this->d->q("SELECT id FROM crawl_uri WHERE path='".$tmp['path'].
			       "' AND domain_id=".$tmp['host'].
			       " AND scheme='".$tmp['scheme'].
			       "';");
    if(empty($r)) {
      $q = $this->d->exec("INSERT INTO crawl_uri (path,domain_id,scheme)" .
		       " VALUES ('".$tmp['path']."', '".$tmp['host']."', '".$tmp['scheme'].
		       "');");
    }
    else {
      return $r[0]['id'];
    }
  }
  
  private function getDomainID($domain) {
    $sql = "SELECT id,name FROM crawl_domain WHERE name like '$domain';";
    $r = $this->d->q($sql);
    if(count($r)===0) {
      $sql = "INSERT INTO crawl_domain (name) VALUES ('".$domain."');";
      $this->d->exec($sql);
      $sql = "SELECT id,name FROM crawl_domain WHERE name like '$domain';";
      $r = $this->d->q($sql);
      return $r[0]['id'];
    }
    else {
      return $r[0]['id'];
    }
  }
  
  private function getSchemeID($scheme) {
    if ($scheme === 'http') { return 0; }
    if ($scheme === 'https') { return 1; }
    $sql = "SELECT id,name FROM protocols WHERE 'name' = '$scheme';";
    $r = $this->db->fetchAssoc($sql);
    if( !is_array($r) || count($r)===0 ) {
      $sql = "INSERT INTO protocols (name) VALUES (:scheme)";
      $q = $this->db->db->prepare($sql);
      $q->execute(array(':scheme'=>$scheme));
      throw new Exception("Scheme dont exitst.... creating... try again");
    }
    else {
      return $r[0]['id'];
    }
  }

  private function fetch($url) {
    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

    $options = array(
		     
		     CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
		     CURLOPT_POST           => false,        //set to GET
		     CURLOPT_USERAGENT      => $user_agent, //set user agent
		     CURLOPT_COOKIEFILE     => "cookie.txt", //set cookie file
		     CURLOPT_COOKIEJAR      => "cookie.txt", //set cookie jar
		     CURLOPT_RETURNTRANSFER => true,     // return web page
		     CURLOPT_HEADER         => false,    // don't return headers
		     CURLOPT_FOLLOWLOCATION => true,     // follow redirects
		     CURLOPT_ENCODING       => "",       // handle all encodings
		     CURLOPT_AUTOREFERER    => true,     // set referer on redirect
		     CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
		     CURLOPT_TIMEOUT        => 120,      // timeout on response
		     CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
		     );
    
    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );
    
    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $header;
  }
}


class RobotsTxt {
  
  private $_domain   = null;
  private $_rules    = array();
  
  public function __construct($domain) {
    $this->log = new Logger();
    $d = new Database();

    $r = $d->q("SELECT id FROM robots limit 1;");
    if (empty($r)) {
      $sql = "CREATE TABLE robots ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
	"url VARCHAR(2048) NOT NULL, " .
	"data TEXT, " .
	"last_fetch TIMESTAMP);";
      $d->exec($sql);
    }


    $this->_domain = $domain;
    $this->_rules = NULL;
    // TODO: add cache age for fetch.
    $r = $d->q("SELECT id FROM robots WHERE url like '$domain' limit 1;");
    if (count($r) < 1) {
       try {
	$robotsTxt     = $this->downloadUrl($domain.'/robots.txt');
	if(!$robotsTxt) return FALSE;
	$this->_rules  = $this->_makeRules($robotsTxt);
	$sql = "INSERT INTO robots (url, data) " .
	  "VALUES (:url, :data)";
	
	
	$this->log->add("Fetched robots.txt from " . $domain . " : " . var_export($sql, TRUE));

	$params = array('url' => 
			$domain,
			'data' => serialize($this->_rules));
	$d->execute($sql, $params);
	
      } catch (Exception $e) {
	 // Could not fetch robots.txt
	 // All is allowed.
      }
    }
    else {
      // load from db
      $r = $d->q("SELECT * FROM robots WHERE url like '$domain' limit 1;");
      $this->_rules = unserialize(array_pop($r)['data']);
    }
  }
  
  private function downloadUrl($url) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_VERBOSE, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
      curl_setopt($ch, CURLOPT_URL, ($url));
      $response = curl_exec($ch);
      curl_close($ch);
      return $response;
  }
  
  /**
   * testet eine url gegen die Regeln der aktuellen robots.txt und
   * gibt zurück, ob sie blockiert ist.
   */
  public function isBlocked($url, $userAgent = '*') {
    /**
     * update: nach dem Hinweis von Naden aus den Kommentaren
     * habe ich die nächsten sieben Zeilen geändert.
     */
    if (!isset($this->_rules[$userAgent])) {
      $rules = isset($this->_rules['*']) ?
	$this->_rules['*'] : array();
    } else {
      $rules = $this->_rules[$userAgent];
    }
    
    if (count($rules) == 0) {
      return false;
    }
    
    // von der URL interessiert uns nur der Teil hinter dem Host
    $urlArray  = parse_url($url);
    if (isset($urlArray['path'])) {
      
      $url = $urlArray['path'];
      
      if (isset($urlArray['query'])) {
	$url .= '?'.$urlArray['query'];
      }
      
      if (isset($urlArray['fragment'])) {
	$url .= '#'.$urlArray['fragment'];
      }
    }
    
    // wenn keine der Regeln passt, ist der Zugriff erlaubt
    $blocked  = false;
    /* wir merken uns die Länge der längsten Regel, weil sie sich gegen
       alle anderen durchsetzt
    */
    $longest  = 0;
    
    foreach ($rules as $r) {
      if (preg_match($r['path'], $url) && (strlen($r['path']) >= $longest)) {
	$longest  = strlen($r['path']);
	$blocked  = !($r['allow']);
      }
    }
    $this->_makeRules($longest);
    
    return $blocked;
  }
  
  /**
   * erstellt ein Array mit den Regeln aus einer gegebenen robots.txt
   */
  private function _makeRules($robotsTxt) {
    $rules  = array();
    $lines  = explode("\n", $robotsTxt);
      
    // verwirf alle Zeile ohne Dis-/allow Anweisung oder Angabe des User-Agents
    $lines  = array_filter($lines, function ($l) {
	return (preg_match('#^((dis)?allow|user-agent)[^:]*:.+#i', $l) > 0);
      });
    
    $userAgent = '';
    foreach ($lines as $l) {
      list($first, $second) = explode(':',$l);
      $first   = trim($first);
      $second  = trim($second);
      
      if (preg_match('#^user-agent$#i', $first)) {
	$userAgent = $second;
      } else {
	if ($userAgent) {
	  $pathRegEx  = $this->_getRegExByPath($second);
	  $allow      = (preg_match('#^dis#i', $first) !== 1);
          
	  $rules[$userAgent][] = array (
					'path'  => $pathRegEx,
					'allow' => $allow,
					);
	}
      }
      if(strcmp(strtolower($first), 'crawl-delay')) {
          $rules['crawl-delay'] = (float)(($second==0)?10:$second);
      }
    } 
    
    return $rules;
  }
  
  /**
   * ermittle den RegEx, der auf die Pfad-Angabe aus der robots.txt pass
   */
  private function _getRegExByPath($path) {
    $regEx  = '';
    $path   = trim($path);
    
    // entschärfe Sonderzeichen
    $regEx  = preg_replace('#([\^+?.()])#','\\\\$1', $path);
    // Sternchen steht für beliebig viele beliebige Zeilen
    $regEx  = str_replace('*', '.*', $regEx);
    
    return '#'.$regEx.'#';
  }
  
}