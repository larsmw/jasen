<?php


class Uri {

  private $uri;
  private $uri_parts;
  private $content_type;
  private $log;
  private $id;
  protected $d;

  public function __construct($url, $uri=NULL) {
    $this->uri = $url;
    $this->uri_parts = parse_url($url);
    if (empty($this->uri_parts['path'])) $this->uri_parts['path'] = "/";
    if (empty($this->uri_parts['host']) && is_object($uri)) 
      $this->uri_parts['host'] = $uri->getHost();
    if (empty($this->uri_parts['host']) && is_string($uri)) {
      $parts = parse_url($uri);
      $this->uri_parts['host'] = $parts['host'];
    }
    if (empty($this->uri_parts['scheme']) && is_object($uri)) 
      $this->uri_parts['scheme'] = $uri->getScheme();
    if (empty($this->uri_parts['path'])) 
      $this->uri_parts['path'] = '/';
    //var_dump($this->uri);
    //var_dump($this->uri_parts);
    $this->d = new Database();
    $this->log = new Logger();
    $this->id = $this->getUrlID($this->uri);
  }

  public function toString() {
    return $this->uri;
  }

  public function getId() {
    return $this->id;
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
    if(empty($tmp['host']) ) {
      return NULL;
      //throw new Exception("missing host :" . 
      //	   var_export($tmp, TRUE));
    }
    if(empty($tmp['scheme'])) {
      $tmp['scheme'] = 'http';
    }
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
    
    //var_dump($this->uri);
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
    file_put_contents("files/" . $uid, $content);
    $sql = "INSERT INTO crawl_stats (url_id, domain_id, fetch_time) VALUES ($uid, $did, " .
      $header['total_time'] . ")";
    $q = $this->d->insert($sql);

    return $header;
  }

  public function addtoqueue() {
    $queue_size = 10000000;
    $sql = "SELECT count(*) as num FROM crawl_queue;";
    $r = $this->d->q($sql);
    if ($r[0]['num'] > $queue_size) {
      //$this->log->add("Did not add '" . $this->uri . "' to queue (full).");
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
    //var_dump($this->uri, "ADD");

    if( count($r)==0 ) {
      $tmp = parse_url($this->uri);
      if (!isset($tmp['host'])) {
	//var_dump($this, __LINE__);
	$tmp['host'] = $this->uri_parts['host'];
      }
      $did = $this->getDomainID($tmp['host']);
      $sql = "INSERT INTO crawl_queue (url_id, did, priority) VALUES ($url_id, $did, $priority)";
      $q = $this->d->exec($sql);
      //$this->log->add("Added " . $this->uri . " to queue.");
    }
    else {
      //$this->log->add("'" . $this->uri . "' is already in queue.");
    }
  }

}

