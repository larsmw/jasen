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
    $this->register('*', 'render', array($this, "render"));
    $this->register('ajax', 'render', array($this, "ajax"));

    if (!$this->d->tableExists("crawl_queue")) {
      $sql = "CREATE TABLE crawl_queue ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
           "url_id INT NOT NULL, did INT NOT NULL, priority INT DEFAULT 1, time_to_crawl TIMESTAMP);";
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

    if (!$this->d->tableExists("crawl_stats")) {
      $sql = "CREATE TABLE crawl_stats ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
           "url_id INT NOT NULL, domain_id INT, time_crawled TIMESTAMP, fetch_time FLOAT);";
      $this->d->exec($sql);
    }


  }

  public function render($r, $e, $p) {

    //$this->robots = new RobotsTxt("http://www.lokalkalender.dk/");

    /*$t = new Template("templates/crawler.tpl");
    //$this->process();
    // show log output
    $sql = "SELECT * FROM cron_log where source like 'Crawler%' order by last_run desc limit 10;";
    $r = $this->d->q($sql);
    $o = "<dl>";
    foreach($r as $row) {
      $o .= "<dt>" . $row['source'] . "</dt>";
      $o .= "<dd>" . $row['message'] . "</dd>";
      $o .= "<dd>" . $row['last_run'] . "</dd>";
    }
    $o .= "</dl>";
    $t->set("content", $o);

    return array('content' => $t->output());*/
  }

  public function ajax($r, $e, $p) {
    $req = new Request();
    if ($req->path(1) != "crawler")
      return;

    // make report
    $sql = "SELECT count(*) as num FROM crawl_domain;";
    $r = $this->d->q($sql);
    $o = "<p>Domains : " . $r[0]['num'] . "</p>";

    $sql = "SELECT * FROM cron_log where source like 'Crawler%' order by last_run desc limit 25;";
    $r = $this->d->q($sql);
    $o .= "<dl>";
    foreach($r as $row) {
      $o .= "<dt>" . $row['source'] . "</dt>";
      $o .= "<dd>" . $row['message'] . "</dd>";
      $o .= "<dd>" . $row['last_run'] . "</dd>";
    }
    $o .= "</dl>";
    $o .= "<p>Dato : " . date("c") . "</p>";
    return array('content' => $o);
  }

  public function cron() {
    $num_crawl_queue_entries = 10000;
    $this->d->q("DELETE FROM crawl_queue WHERE id NOT IN (SELECT id FROM ( select id from crawl_queue order by id desc limit " .$num_crawl_queue_entries. " ) foo );");
    $this->process();
  }

  /*
    Curl sample response : 
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
  private function process() {
    echo "START\n";
    $arr_http_code_ok = array(200);
    $good_content_types = array(
				"text/html",
				"text/html; charset=utf-8",
				"text/html;charset=utf-8",
				"text/html; charset=iso-8859-1",
				"text/html;charset=iso-8859-1",
				);
    $uri_list = $this->getUriList(1);
    if (is_null($uri_list)) {
      echo "ERRORRRR -- list of urls to crawl is empty.\n";
      return;
    }

    foreach($uri_list as $k=>$a_uri) {
      if (is_array($a_uri)) {
	var_dump($a_uri);
	$uri = Uri::loadById($a_uri['url_id']);
      } else {
	echo __LINE__;
	$uri = Uri::loadById($a_uri);
      }
      echo __LINE__;
      var_dump($uri);

      $this->robots = new RobotsTxt($uri->getHost());

      if (!$this->robots->isBlocked($uri->getPath())) {
	if (!$this->robots->isTime()) {
	  continue;
	}
	$this->log->add("I Will crawl : <a href=" . $uri->toString() . " target=\"_BLANK\">" . $uri->toString() . "</a>");
	$response = $uri->fetch();
	echo "  - http:".$response['http_code']." ";

	if (!in_array(strtolower(trim($uri->getContentType())), $good_content_types)) {
	  echo __LINE__;
	  var_dump($uri->getContentType());
	  continue;
	}
	//$this->msg("response : " . var_export($response, TRUE));
	if (in_array($response['http_code'], $arr_http_code_ok)) {
	  $this->msg("response : total_time=" . 
		     var_export($response['total_time'], TRUE)."s.");
	  echo $response['total_time'];
	  libxml_use_internal_errors(TRUE);
	  $DOM = new DOMDocument();
	  //load the html string into the DOMDocument
	  $DOM->loadHTML($response['content']);
	  foreach (libxml_get_errors() as $error) {
	    // handle errors here
	    // Here we get the structural errors in the downloaded pages.
	  }
	  libxml_use_internal_errors(FALSE);
	  //get a list of all <A> tags
	  echo "Get a tags....\n";
	  $a = $DOM->getElementsByTagName('a');
	  //loop through all <A> tags
	  foreach($a as $link){
	    $new_url = parse_url($link->getAttribute('href'));
	    if (empty($new_url['host'])) $new_url['host'] = $uri->getHost();
	    if (empty($new_url['scheme'])) $new_url['scheme'] = $uri->getScheme();
	    if (empty($new_url['path'])) $new_url['path'] = "";
	    //$this->msg("link-text : " . var_export($link->nodeValue, TRUE));
	    //$this->msg("link : " . var_export($new_url, TRUE));
	    $n = $new_url['scheme']."://".$new_url['host'].$new_url['path'];
	    $u = new Uri($n);
	    $u->addtoqueue();
	  }
	  echo "trigger index\n";
	  Events::trigger('content', 'index', 
			  ['content' => $response['content']]);
	}
	else {
	  echo __LINE__;
	  var_dump($response['http_code']);
	}
      }
      else {
	// url is blocked by robots!?
	$this->log->add("This is blocked : <a href=" . $uri . ">" . $uri . "</a>");
	echo " Blocked!";
      }
      echo "\n";
    }
    echo "DONE\n";
  }

  private function getUriList($count = 1) {
    $delay = 30;
    $sql = "select q.id, q.url_id from crawl_queue q WHERE q.priority=2 group by q.did order by time_to_crawl asc limit " . $count . ";";
    $r = $this->d->q($sql);
    if (empty($r)) {
      //return array("https://en.wikipedia.org/wiki/Main_Page");
      $sql = "select q.id, q.url_id from crawl_queue q WHERE q.priority=1 group by q.did order by time_to_crawl asc limit " . $count . ";";
      $r = $this->d->q($sql);
      if (empty($r)) {
	$sql = "select q.id, q.url_id from crawl_queue q WHERE q.priority=0 group by q.did order by time_to_crawl asc limit " . $count . ";";
	$r = $this->d->q($sql);
	if (empty($r)) {
	  return null;
	} else {
	  foreach($r as $url) {
	    //var_dump($url);
	    $d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
	  }
	  //echo "prio=0\n";
	  return $r;
	}
      } else {
	foreach($r as $url) {
	  //var_dump($url);
	  $d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
	}
	//echo "prio=1\n";
	return $r;
      }
    } else {
      foreach($r as $url) {
	//var_dump($url);
	$d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
      }
      //echo "prio=2\n";
      return $r;
    }
  }

}

class Uri {

  private $uri;
  private $uri_parts;
  private $content_type;
  protected $d;

  public function __construct($url) {
    $this->uri = $url;
    $this->uri_parts = parse_url($url);
    if(empty($this->uri_parts['path'])) $this->uri_parts['path'] = "/";
    $this->d = new Database();
  }

  public function toString() {
    return $this->uri;
  }

  public function getHost() {
    return $this->uri_parts['host'];
  }

  public function getScheme() {
    return $this->uri_parts['scheme'];
  }

  public function getPath() {
    return $this->uri_parts['path'];
  }

  public function getContentType() {
    return $this->content_type;
  }

  public function loadById($id) {
    $d = new Database();
    /*SELECT * FROM linkhub.crawl_uri u inner join crawl_domain d on u.domain_id=d.id WHERE u.id=13423;*/
    $sql = "SELECT * FROM linkhub.crawl_uri u inner join";
    $sql .= " crawl_domain d on u.domain_id=d.id WHERE u.id=".$id.";";
    $r = $d->q($sql);
    return new Uri($r[0]['scheme']."://".$r[0]['name'].$r[0]['path']);
  }

  private function getUrlID($url) {
    $tmp = parse_url($url);
    if(empty($tmp['host'])) throw new Exception("missing host :" . 
		   var_export($tmp, TRUE));
    //$tmp['host'] = $base['host'];
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
      $id = $this->d->insert($sql);
      /*echo __LINE__;
      var_dump($domain);
      echo __LINE__;
      var_dump($id);*/
      //$sql = "SELECT id,name FROM crawl_domain WHERE name like '$domain';";
      //$r = $this->d->q($sql);
      return $id;
    }
    else {
      return $r[0]['id'];
    }
  }
  
  private function getSchemeID($scheme) {
    if ($scheme === 'http') { return 0; }
    if ($scheme === 'https') { return 1; }
    $sql = "SELECT id,name FROM protocols WHERE 'name' = '$scheme';";
    $r = $this->d->fetchAssoc($sql);
    if( !is_array($r) || count($r)===0 ) {
      $sql = "INSERT INTO protocols (name) VALUES ($scheme)";
      $q = $this->d->insert($sql);
      throw new Exception("Scheme dont exitst.... creating... try again");
    }
    else {
      return $r[0]['id'];
    }
  }

  public function fetch() {
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
    
    $ch      = curl_init( $this->uri );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );
    
    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    
    $this->content_type = $header['content_type'];

    $uid = $this->getUrlID($this->uri);
    $did = $this->getDomainID($this->uri_parts['host']);
    $sql = "INSERT INTO crawl_stats (url_id, domain_id, fetch_time) VALUES ($uid, $did, " .
      $header['total_time'] . ")";
    $q = $this->d->insert($sql);

    return $header;
  }

  public function addtoqueue() {
    $sql = "SELECT count(*) as num FROM crawl_queue;";
    $r = $this->d->q($sql);
    if ($r[0]['num'] > 1000) {
      //$this->log->add("Did not add '" . $url . "' to queue.");
      return;
    }

    if (strstr($this->uri, "wikipedia.org") ||
	strstr($this->uri, "wikinews.org") ||
	strstr($this->uri, "mediawiki.org") ||
	strstr($this->uri, "wikiquote.org") ||
	strstr($this->uri, "wikidata.org") ||
	strstr($this->uri, "wikimediafoundation.org")
	) {
      // std priority is 1
      $priority = 0;
    } else if (strstr($this->uri, ".dk")) {
      $priority = 2;
    }
    else {
      $priority = 1;
    }
    $url_id = $this->getUrlID($this->uri);
    $sql = "SELECT id FROM crawl_queue WHERE url_id = $url_id;";
    $r = $this->d->q($sql);

    if( count($r)==0 ) {
      $tmp = parse_url($this->uri);
      $did = $this->getDomainID($tmp['host']);
      $sql = "INSERT INTO crawl_queue (url_id, did, priority) VALUES ($url_id, $did, $priority)";
      $q = $this->d->exec($sql);
      //$this->msg("Added " . $n . " to queue.");
    }
    else {
      //$this->log->add("Did not add '" . $url . "' to queue.");
    }
  }

}

class RobotsTxt {
  
  private $_domain   = null;
  private $_rules    = array();
  
  public function __construct($domain) {
    $this->log = new Logger();
    $d = new Database();

    if (!$d->tableExists("robots")) {
      $sql = "CREATE TABLE robots ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
	"did INT NOT NULL, " .
	"delay INT NOT NULL DEFAULT 15," . 
	"data TEXT, " .
	"last_fetch TIMESTAMP);";
      $d->exec($sql);
    }


    $this->_domain = $domain;
    $this->_rules = NULL;
    $up = parse_url($domain);
    $did = $this->getDomainID($up['host']);
    // TODO: add cache age for fetch.
    $r = $d->q("SELECT id FROM robots WHERE did like '$did' limit 1;");
    if (count($r) < 1) {
       try {
	$robotsTxt     = $this->downloadUrl($domain.'/robots.txt');
	if(!$robotsTxt) return FALSE;
	$this->_rules  = $this->_makeRules($robotsTxt);
	$sql = "INSERT INTO robots (did, data, delay) " .
	  "VALUES (:did, :data, :delay)";
	
	$this->log->add("Fetched robots.txt from " . $domain . " : " . var_export($sql, TRUE));

	$params = array('did' => $did,
			'data' => serialize($this->_rules), 
			'delay' => $this->_rules['crawl-delay']);
	$d->execute($sql, $params);
	
      } catch (Exception $e) {
	 // Could not fetch robots.txt
	 // All is allowed.
      }
    }
    else {
      // load from db
      $r = $d->q("SELECT * FROM robots WHERE did like '$did' limit 1;");
      $this->_rules = unserialize(array_pop($r)['data']);
    }
  }
  
  public function downloadUrl($url) {
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
    $default_delay = 10;      
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
          $rules['crawl-delay'] = (float)(($second==0)?$default_delay:$second);
      }
    } 
    if (!isset($rules['crawl-delay'])) 
      $rules['crawl-delay'] = $default_delay;
    return $rules;
  }

  /*
   * Is it time for next crawl?
   * @return: bool
   */
  public function isTime() {
    $d = new Database();
    $url_parts = parse_url($this->_domain);
    $did = $this->getDomainId($url_parts['host']);
    $sql = "SELECT * FROM crawl_stats WHERE domain_id='".$did."' order by time_crawled desc limit 1;";
    $r = $d->q($sql);
    if (empty($r)) {

      echo "First visit : " . $this->_domain . "\n";
      return TRUE;
    }
    $visit = strtotime($r[0]['time_crawled']);
    $now = strtotime(date('c'));
    if (($now - $visit) > $this->_rules['crawl-delay']) {
      // ok, we have waited enough.
      echo "crawl-delay : " . $this->_rules['crawl-delay'] . "\n";
      return TRUE;
    }
    else {
      echo "Too early to crawl : " . $this->_domain . " Delay : ".$this->_rules['crawl-delay']."\n";
      return FALSE;
    }
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
  
  private function getDomainID($domain) {
    $d = new Database();
    $sql = "SELECT id,name FROM crawl_domain WHERE name like '$domain';";
    $r = $d->q($sql);
    if(count($r)===0) {
      $sql = "INSERT INTO crawl_domain (name) VALUES ('".$domain."');";
      $id = $d->insert($sql);
      /*echo __LINE__;
      var_dump($domain);
      echo __LINE__;
      var_dump($id);*/
      //$sql = "SELECT id,name FROM crawl_domain WHERE name like '$domain';";
      //$r = $this->d->q($sql);
      return $id;
    }
    else {
      return $r[0]['id'];
    }
  }
}