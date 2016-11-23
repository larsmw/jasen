<?php

namespace LinkHub\Modules;

class robots {
  
  private $_uri   = null;
  private $did;
  private $_rules    = array();
  
  public function __construct(&$uri) {
    $d = new \Database();

    $this->_uri = $uri;
    $this->_rules = NULL;
    $this->did = $uri->getDomainID();
    // TODO: add cache age for fetch.
    $r = $d->fetchAssoc("SELECT * FROM robots WHERE did like '$this->did' limit 1;");
    if (count($r) < 1) {
      $this->update();
    }
    else {
      // load from db
      if (strtotime($r[0]['last_fetch']) > (time() - 12*60*60)) {
        // robots.txt is less than 12 hours old
        $this->_rules = unserialize(array_pop($r)['data']);
      }
      else {
        // update robots.txt
        $this->update();
      }
    }
  }

  private function update() {
    try {
      $robotsTxt = $this->downloadUrl($this->_uri->getScheme()."://" .
                   $this->_uri->getHost().'/robots.txt');
      if(!$robotsTxt) return FALSE;
      $this->_rules  = $this->_makeRules($robotsTxt);
      $this->save();
      
    } catch (Exception $e) {
      // Could not fetch robots.txt
      // All is allowed.
    }
  }
  
  private function save() {
    $d = new \Database();
    $r = $d->fetchAssoc("SELECT * FROM robots WHERE did like '".$this->_uri->getDomainID()."' limit 1;");
    if (count($r) < 1) {
      $sql = "INSERT INTO robots (did, data, delay) VALUES (".$this->_uri->getDomainID().
           ", '".serialize($this->_rules)."', ".$this->_rules['delay'].")";
      
      $d->exec($sql);
      var_dump("saved robots");
    }
    else {
      $sql = "UPDATE robots set data='" . serialize($this->_rules) 
	. "', delay='" . $this->_rules['delay'] . "' " .
	"WHERE did=" . $this->did . ";";
      
      $d->exec($sql);
      var_dump("updated robots");
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
    $d = new \Database();
    $nice_margin = 1800; // wait X seconds between visits.
    $url_parts = parse_url($this->_uri->getHost());
    //$did = $this->getDomainId($this->_domain);
    $sql = "SELECT * FROM crawl_stats WHERE domain_id=".$this->_uri->getDomainID()." order by time_crawled desc limit 1;";
    $r = $d->fetchAssoc($sql);
    //var_dump($this->_uri->getDomainID());
    //var_dump($r);
    if (empty($r)) {
      //echo "First visit : " . $this->_domain . "\n";
      return TRUE;
    }
    $visit = strtotime($r[0]['time_crawled']);
    $now = strtotime(date('c'));
    //var_dump($visit);
    //var_dump($now);
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
  
}