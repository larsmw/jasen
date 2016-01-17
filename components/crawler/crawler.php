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
	"url_id INT NOT NULL, time_added TIMESTAMP);";
      $this->d->exec($sql);
    }
    if (!$this->d->tableExists("crawl_uri")) {
      $sql = "CREATE TABLE crawl_uri ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " . 
	"part VARCHAR(4096));";
      $this->d->exec($sql);
    }
    if (!$this->d->tableExists("crawl_domain")) {
      $sql = "CREATE TABLE crawl_domain ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " . 
	"name VARCHAR(4096));";
      $this->d->exec($sql);
    }
  }

  public function render($r, $e, $p) {

    $this->robots = new RobotsTxt("http://www.lokalkalender.dk/");
    //var_dump($this->robots->isBlocked("/includes/test"));

    $db = new Database();
    $t = new Template("templates/user.tpl");
    $t->set("user_name", "jeg mangler en template-fil");
    $t->set("css_class", 'user');
    return array('sidebar' => $t->output());
  }

  public function cron() {
    $this->log->add("crawler starter");
    $this->process();
  }

  private function process() {
    foreach($this->getUriList() as $uri) {
      $uri_parts = parse_url($uri);
      if(empty($uri_parts['path'])) $uri_parts['path'] = "/";
      $this->log->add(var_export($uri_parts, TRUE));
      $this->robots = new RobotsTxt($uri_parts['scheme']."://" . $uri_parts['host']);
      if (!$this->robots->isBlocked($uri_parts['path'])) {
	$this->log->add("I Will crawl : " . var_export($uri_parts, TRUE) . var_export($uri, TRUE));
      }
    }
  }

  private function getUriList($count = 1) {
    $r = $this->d->q("SELECT id FROM crawl_queue limit " . $count . ";");
    if (empty($r)) {
      return array("http://www.dmoz.org/Computers/News_and_Media/");
    } else {
      return $r;
    }
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
    $r = $d->q("SELECT id FROM robots WHERE url like '$domain' limit 1;");
    if (count($r) < 1) {
       try {
	$robotsTxt     = $this->downloadUrl($domain.'/robots.txt');
	if(!$robotsTxt) return FALSE;
	$this->_rules  = $this->_makeRules($robotsTxt);
	//var_dump($this->_rules);
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