<?php


class RobotsTxt {
  
  private $_domain   = null;
  private $did;
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

    $this->did = $this->getDomainID($domain);
    // TODO: add cache age for fetch.
    $r = $d->q("SELECT * FROM robots WHERE did like '$this->did' limit 1;");
    if (count($r) < 1) {
       try {
	$robotsTxt     = $this->downloadUrl($domain.'/robots.txt');
	if(!$robotsTxt) return FALSE;
	$this->_rules  = $this->_makeRules($robotsTxt);

	/*$sql = "INSERT INTO robots (did, data, delay) " .
	  "VALUES (:did, :data, :delay)";
	

	$params = array('did' => $this->did,
			'data' => serialize($this->_rules), 
			'delay' => $this->_rules['crawl-delay']);
			$d->execute($sql, $params);*/
	$this->save();
	//$this->log->add("Fetched robots.txt from " . $domain . " : " . var_export($sql, TRUE));
	
      } catch (Exception $e) {
	 // Could not fetch robots.txt
	 // All is allowed.
      }
    }
    else {
      // load from db
      //$this->log->add("In DB - robots.txt from " . $domain . " : " . var_export($r, TRUE));
      $this->_rules = unserialize(array_pop($r)['data']);
      //$this->log->add("loaded - robots.txt from " . $domain . " : " . var_export($this->_rules, TRUE));
      /*      if ( //last fetch too long ago
	  strtotime($this->_rules[
 ) {
	$robotsTxt     = $this->downloadUrl($domain.'/robots.txt');
	$this->_rules  = $this->_makeRules($robotsTxt);
	$this->save();
	}*/
    }
  }

  private function save() {
    $d = new Database();
    $r = $d->q("SELECT * FROM robots WHERE did like '$this->did' limit 1;");
    if (count($r) < 1) {
      $sql = "INSERT INTO robots (did, data, delay) " .
	"VALUES (:did, :data, :delay)";
      
      $params = array('did' => $this->did,
		      'data' => serialize($this->_rules), 
		      'delay' => $this->_rules['delay']);
      $d->execute($sql, $params);
      //$this->log->add("saved new robots.txt in db from " . $this->_domain . " : " . var_export($r, TRUE));
    }
    else {
      $sql = "UPDATE robots set data='" . serialize($this->_rules) 
	. "', delay='" . $this->_rules['delay'] . "') " .
	"WHERE did=" . $this->did . ";";
      
      $d->exec($sql);
      $this->log->add("updated robots.txt in db from " . $this->_domain . " : " . var_export($r, TRUE));
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
          $rules['delay'] = (float)(($second==0)?$default_delay:$second);
      }
    } 
    if (!isset($rules['delay'])) 
      $rules['delay'] = $default_delay;
    return $rules;
  }

  /*
   * Is it time for next crawl?
   * @return: bool
   */
  public function isTime() {
    $d = new Database();
    $nice_margin = 1800; // wait X seconds between visits.
    $url_parts = parse_url($this->_domain);
    //$did = $this->getDomainId($this->_domain);
    $sql = "SELECT * FROM crawl_stats WHERE domain_id='".$this->did."' order by time_crawled desc limit 1;";
    $r = $d->q($sql);
    if (empty($r)) {

      //echo "First visit : " . $this->_domain . "\n";
      return TRUE;
    }
    $visit = strtotime($r[0]['time_crawled']);
    $now = strtotime(date('c'));
    if (!isset($this->_rules['delay'])) {
      $this->_rules['delay'] = 10;
      $this->save();
    }
    if (($now - $visit - $nice_margin) > $this->_rules['delay']) {
      // ok, we have waited enough.
      //$this->log->add("crawl-delay : " . $this->_rules['delay'] . "\n");
      return TRUE;
    }
    else {
      $this->log->add("Too early to crawl : " . $this->_domain . 
		      " Delay : ".$this->_rules['delay']."\n");
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