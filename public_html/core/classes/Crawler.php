<?php

namespace Crawler;

require_once "Database.php";
require_once "Robots.php";
require_once "Interfaces.php";
require_once "Base.php";

class Domain {
  private $name,$id;
  protected $db;

  public function __construct($url) {
    $this->db = new \Database();
    $url_parts = parse_url($url);
    //var_dump($url_parts);
    $this->name = isset($url_parts['host'])?$url_parts['host']:$url_parts['path'];
  }

  public function getID() {

    if(isset($this->id)) return $this->id;

    if(is_null($this->name)) return NULL;

    $this->id = $this->db->insertGetId("domain", $this->name);
    return $this->id;
  }
}

class Url {
  private $domain,$parts,$url,$db,$id;

  public function __construct($url) {
    $this->db = new \Database();
    $this->url = $url;
    $this->domain = new Domain($url);
    $this->id = $this->getId();
    $this->parts = parse_url($url);
    /*
      var_dump($this->parts);
      array (size=3)
      'scheme' => string 'http' (length=4)
      'host' => string 'www.pfizer.co.kr' (length=16)
      'path' => string '/' (length=1)*/
    //var_dump($this->domain->getID(), $this->getId());
  }

  public function valid() {
    return (!empty($this->parts['host'])) && (filter_var($this->url, FILTER_VALIDATE_URL) == TRUE);
  }

  public function getId() {
    if(isset($this->id)) 
      return $this->id;
    $tmp['scheme'] = $this->getSchemeID($this->parts['scheme']);
    $tmp['host'] = $this->domain->getID();
    if(!isset($tmp['path'])) $tmp['path'] = '/';
    $ret[] = $tmp;
    
    $r = $this->db->fetchAssoc("SELECT id FROM urls WHERE url='".$tmp['path'].
			       "' AND domain_id=".$tmp['host'].
			       " AND scheme_id=".$tmp['scheme'].
			       ";");
    //var_dump($tmp,$r);
    if(count($r)) {
      $this->id = $r[0]['id'];
      return $r[0]['id'];
    }
    else
      {
        $sql = "INSERT INTO urls (url,domain_id,scheme_id) VALUES (:url,:host,:scheme)";
        $q = $this->db->db->prepare($sql);
        $q->execute(array(':url'=>$tmp['path'],
            ':host'=>$tmp['host'],
            ':scheme'=>$tmp['scheme']));
        $this->id = $this->db->db->lastInsertId();
        return $this->id;
      }
    
  }

  public function updateNextVisit() {
    $sql = "UPDATE domain SET next_visit=DATE_ADD(NOW(), INTERVAL :sec SECOND) WHERE id=:id;";
    $q = $this->db->db->prepare($sql);
    $q->execute(array(':id'=>$this->domain->getID(), ':sec'=> 15 ));
  }

  public function getUrl() {
    return $this->url;
  }

  public function getHref() {
    return '<a href="'.$this->getUrl().'" target="_BLANK">'.$this->limit_text($this->getUrl(), 30).'</a>';
  }

  public function limit_text($text, $len=200) {
    if (strlen($text) < $len) {
      return $text;
    }
    $text_words = explode(' ', $text);
    $out = null;
    
    foreach ($text_words as $word) {
      if ((strlen($word) > $len) && $out == null) {
	
        return substr($word, 0, $len) . "...";
      }
      if ((strlen($out) + strlen($word)) > $len) {
        return $out . "...";
      }
      $out.=" " . $word;
    }
    return $out;
  }
   
  private function getSchemeID($scheme) {
    return $this->db->insertGetId("protocols", trim($scheme));
  }

  public function getServerId($serverid) {
    return $this->db->insertGetId("server", $serverid);
  }

  public function getGeneratorId($str) {
    return $this->db->insertGetId("generators", $str);
  }

  public function save($content) {
    $path = "files/".$this->domain->getID();
    if (!is_dir($path)) {
      if(!mkdir($path, '0755')) {
        return false;
      }
    }
    file_put_contents($path."/".$this->getId(), $content);
	  $sql = "UPDATE urls SET ondisk=1 WHERE id=:url;";
	  $q = $this->db->db->prepare($sql);
	  $q->execute(array(':url'=>$this->getId()));
  }
}

class Crawler extends \Base implements \Plugin {

    protected $db;

    private static $std_crawldelay = 600;
    private $msgStack;

    public function __construct()
    {
      parent::__construct();
      date_default_timezone_set("Europe/Copenhagen");
      $this->db = new \Database();
      $this->msgStack = new \SplStack();
    }

    protected function __clone()
    {
      //Me not like clones! Me smash clones!
    }

    public function getReport() {
        $r = $this->db->fetchAssoc("SELECT count(*) as num FROM urls;");
        $s = "<h3>Stats</h3>";
        $s .= "Links : ".$r[0]['num']."<br />";
        $r = $this->db->fetchAssoc("SELECT count(*) as num FROM domain;");
        $s .= "Domains : ".$r[0]['num']."<br />";
	//echo $s;
        return 0;
    }

    /*
     * Starts a run of the crawler.
     */
    public function run( $data ) {
      //var_dump( $data );
      if($data->access(array('crawler run'))) {
        if(isset($_GET['q'])) {
          if($_GET['q'] === 'crawl' ) {
            $this->doRun(3);
          }
        }
      }
    }

    public function ajax_run() {
      $u = new \User();
      if($u->access(array('crawler run'))) {
        syslog(LOG_NOTICE, "Crawler backgrounding...");
        $pid = \pcntl_fork(); // fork
        if ($pid < 0)
          exit;
        else if ($pid) // parent
          exit;
        else { // child
          $sid = \posix_setsid();
          
          if ($sid < 0)
            exit;
          $this->doRun();
        }
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

    /*
     * Do the run...
     */
    private function doRun($numUrls = 10) {
      //ob_start();
      //echo "<html><head>";
      //echo "<meta http-equiv=\"refresh\" content=\"5\">";
      //echo date(DATE_ATOM)."<br />\n";

      $urls = $this->getUrls($numUrls);
      $dl_total = 0;
      // For each url that should be crawled
      foreach($urls as $Url) {
        $out = "<b>Crawling : </b>" . $Url->getHref()."";

        if(!$Url->valid()) {
          $out .= "<b>Invalid url</b><br />";
          continue;
        }
	  
        //if(!isset($url_part['path'])) $url_part['path'] = "/";

        $Url->updateNextVisit();

        // delete domain if wrong
        /*            if($db_id != $this->getDomainID($url['url'])) {
                      var_dump($db_id);
                      $sql = "DELETE FROM domain WHERE id = :cid;";
                      $q = $this->db->db->prepare($sql);
                      $q->BindParam('cid', $db_id, \PDO::PARAM_INT);
                      $q->execute();
                      }*/

        $url_part = parse_url($Url->getUrl());
        try {
          $robot = new \robotstxt($url_part['scheme']."://".$url_part['host']);
        }
        catch( Exception $e ) {
        }
        if($robot->isUrlBlocked($Url->getUrl())) {
          $out .= " <b>Blocked by robots</b><br />";
          continue;
        }
        //var_dump($Url->getId());
        $start_time = microtime(true);
        $headers = @get_headers($Url->getUrl(),1);
        $end_time = microtime(true);
        $fetch_seconds = $end_time - $start_time;
        $download_seconds = 0;

        if(!$headers) {
          $out .= "Failed to get headers...<br />";
          continue;
        }
        $server_id = 0;
        $generator_id = 0;
        if(isset($headers['Server'])) {
          //var_dump($headers['Server']);
          $server_id = $Url->getServerId($headers['Server']);
        }
        else {
          //var_dump($headers);
        }
        if(isset($headers['X-Generator'])) {
          $generator_id = $Url->getGeneratorId($headers['X-Generator']);
        }
        else {
          //var_dump($headers);
        }


        $run = true;
        switch($headers[0]) {
          case 'HTTP/1.1 301 Moved Permanently' :
          case 'HTTP/1.1 301 MOVED PERMANENTLY' :
          case 'HTTP/1.0 301 Moved Permanently' :
          case 'HTTP/1.0 301 Redirect' :
          case 'HTTP/1.1 301' :
          case 'HTTP/1.1 301 ' :
          case 'HTTP/1.1 302 Moved Temporarily' :
          case 'HTTP/1.0 302 Moved Temporarily' :
          case 'HTTP/1.0 302 Found':
          case 'HTTP/1.1 302 Found':
          case 'HTTP/1.1 302 Object moved' :
          case 'HTTP/1.1 302 Object Moved':
          case 'HTTP/1.1 302 Redirect' :
            // Moved - follow link
            $out .= "&nbsp;<b>" . $headers[0] ."</b>";
            //var_dump($headers['Location']);
            if(is_array($headers['Location'])) {
              $location = $headers['Location'][0];
            }
            else
              {
                $location = $headers['Location'];
              }
            $out .= " -> " . $location . "<br />";
            $tmp = parse_url($location);
            $base = parse_url($Url->getUrl());
            if(empty($tmp['host'])) $tmp['host'] = $base['host'];
            if(empty($tmp['scheme'])) $tmp['scheme'] = $base['scheme'];
            $tmp['scheme'] = $this->getSchemeID($tmp['scheme']);
            $tmp['host'] = $this->getDomainID($tmp['host']);
            if(!isset($tmp['path'])) $tmp['path'] = '/';
            $ret[] = $tmp;
            //var_dump($tmp);
            $this->addCrawlerQueue($tmp['path'], $tmp['host']);
            $run = false;
            break;
          case 'HTTP/1.1 500 Internal Server Error' : 
          case 'HTTP/1.1 400 Bad Request' :
          case 'HTTP/1.1 404 Not Found' :
          case 'HTTP/1.0 403 Forbidden' :
          case 'HTTP/1.1 403 Forbidden' :
            // Wrong - remove link
            $run = false;
            break;
          case 'HTTP/1.0 200 OK' :
          case 'HTTP/1.1 200 OK' :
          case 'HTTP/1.1 200 OK ' :
          case 'HTTP/1.1 200 Okay' :
          case 'HTTP/1.1 200 ' :
            // All is fine
            break;
          default:
            var_dump($headers[0]);
        }
	  
        if($run) {
          $contenttype = $headers['Content-Type'];
          //var_dump($contenttype);
          switch($contenttype) {
            case "text/html" : 
            case "text/html;charset=utf-8" :
            case "text/html; charset=utf-8" :
            case "text/html;charset=UTF-8" :
            case "text/html; charset=UTF-8" :
            case "text/html; charset=ISO-8859-1" :
            case "text/html; charset=iso-8859-1" :
            case "text/html;charset=iso-8859-1" :
            case "text/html;charset=ISO-8859-1" :
              $start_time = microtime(true);
              $response = $this->downloadUrl($Url->getUrl());
              $end_time = microtime(true);
              $download_seconds = $end_time - $start_time;

              // fetch urls from response
              $Url->save(
                strip_tags(
                  $this->removeDomNodes($this->removeDomNodes($response, '//script'), '//style'), 
                  "<title><b><i><p><a><br>")); // remove scripts, preserve <tags> and save to file.

              $dl_length = strlen($response);
	      //echo " - 1 Downloaded <b>".$this->format_size($dl_length)."</b> bytes.<br />";
              $dl_total += $dl_length;
	      
              $urls = $this->pageLinks($response, $Url->getUrl(), true);
              //                var_dump($urls);
              //echo count($links);
              break;
            default:
              //                echo var_export($contenttype, TRUE)." : ";
              //var_dump($contenttype); 
              break;
          }
        }
        $sql = "INSERT INTO url_visits (url_id,visit_time,fetch_time_head,fetch_time_full) VALUES (:url,NOW(),:fetch,:full)";
        $q = $this->db->db->prepare($sql);
        $q->execute(array(':url'=>$Url->getId(),
            ':fetch'=>$fetch_seconds,
            ':full'=>$download_seconds));
        $sql = "UPDATE urls SET server=:server, generator=:generator WHERE id=:url;";
        $q = $this->db->db->prepare($sql);
        $q->execute(array(':url'=>$Url->getId(),
            ':server'=>$server_id,
            ':generator'=>$generator_id));
        $this->showStatus();
      }
      $out .= "<p>Totally downloaded : <b>".$this->format_size($dl_total)."</b>.</p>";
      $out .= xdebug_memory_usage()." bytes memory<br />";
      $out .= xdebug_time_index()." seconds";
      //echo $out;
    }
    
    // $html        = the html on the page
// $current_url = the full url that the html came from (only needed for $repath)
// $repath      = converts ../ and / and // urls to full valid urls
    function pageLinks($html, $current_url = "", $repath = false) {
        preg_match_all("/\<a.+?href=(\"|')(?!javascript:|#)(.+?)(\"|')/i", $html, $matches);
        $links = array();
        $ret = array();
        //var_dump($matches[2]);var_dump($current_url);
        if(isset($matches[2])){
            $links = $matches[2];
        }
        if($repath && count($links) > 0 && strlen($current_url) > 0){
            $pathi      = pathinfo($current_url);
            $dir        = $pathi["dirname"];
            $base       = parse_url($current_url);
            $split_path = explode("/", $dir);
            $url        = "";
            foreach($links as $k => $link){
                if(preg_match("/^\.\./", $link)){
                    $total = substr_count($link, "../");
                    for($i = 0; $i < $total; $i++){
                        array_pop($split_path);
                    }
                    $url = implode("/", $split_path) . "/" . str_replace("../", "", $link);
                }elseif(preg_match("/^\/\//", $link)){
                    $url = $base["scheme"] . ":" . $link;
                }elseif(preg_match("/^\/|^.\//", $link)){
                    $url = $base["scheme"] . "://" . $base["host"] . $link;
                }elseif(preg_match("/^[a-zA-Z0-9]/", $link)){
                    if(preg_match("/^http/", $link)){
                        $url = $link;
                    }else{
                        $url       = $dir . "/" . $link;
                    }
                }
                $links[$k] = $url;
            }

            foreach($links as $link) {
                // Dont add invalid urls
                $url_part = parse_url($link);
                if(empty($url_part['host']) || (filter_var($link, FILTER_VALIDATE_URL) == false)) {
                    continue;
                }
                $tmp = parse_url($link);
                if(empty($tmp['host'])) $tmp['host'] = $base['host'];
                if(empty($tmp['scheme'])) $tmp['scheme'] = $base['scheme'];
                $tmp['scheme'] = $this->getSchemeID($tmp['scheme']);
                $tmp['host'] = $this->getDomainID($tmp['host']);
                if(!isset($tmp['path'])) $tmp['path'] = '/';
                $ret[] = $tmp;
                //var_dump($tmp);
                $this->addCrawlerQueue($tmp['path'], $tmp['host']);

                $r = $this->db->fetchAssoc("SELECT count(*) as num FROM urls WHERE url='".$tmp['path'].
                                           "' AND domain_id=".$tmp['host'].
                                           " AND scheme_id=".$tmp['scheme'].
                                           ";");
                if($r[0]['num'] < 1) {
                    $sql = "INSERT INTO urls (url,domain_id,scheme_id) VALUES (:url,:host,:scheme)";
                    $q = $this->db->db->prepare($sql);
                    $q->execute(array(':url'=>$tmp['path'],
                                      ':host'=>$tmp['host'],
                                      ':scheme'=>$tmp['scheme']));
                }
            }
        }
        return $ret;
    }

    /*
     * Get Urls to be crawled.
     */
    private function getUrls($num) {
      $sql = "SELECT id FROM `domain` WHERE next_visit<NOW() order by next_visit ASC limit " . $num . ";";
      $ids = $this->db->fetchAssoc($sql);
      //var_dump($ids);
      if(empty($ids)) return NULL;
      foreach($ids as $id) {
        $db_ids[] = intval($id['id']);
      }
      
      $urls = array();
      
      foreach($db_ids as $db_id) {
        $sql = "SELECT id,url FROM crawl_queue WHERE domain_id = '".$db_id."' order by id ASC limit 1;";
        $url = $this->db->fetchAssoc($sql);
        //var_dump($url);
        if(!count($url)) {
          // Crawl frontpage
          $url[0]['url'] = "";
        }
        //$url = $url[0];
        $sql = "SELECT name FROM domain WHERE id=$db_id;";
        $r = $this->db->fetchAssoc($sql);
        //var_dump($r,$url);
        $url[0]['url'] = "http://".rtrim($r[0]['name'], "//")."/".ltrim($url[0]['url'], "/");
        
        // remove url from crawl_queue
        $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
        $q = $this->db->db->prepare($sql);
        $q->BindParam('cid', $url[0]['id'], \PDO::PARAM_INT);
        $q->execute();
        
        $urls[] = new Url($url[0]['url']);
      }
      //var_dump($urls);
      return $urls;
    }

    private function removeDomNodes($html, $xpathString)
    {
      $dom = new \DOMDocument;
      @$dom->loadHtml($html);
      
      $xpath = new \DOMXPath($dom);
      while ($node = $xpath->query($xpathString)->item(0)) {
        $node->parentNode->removeChild($node);
      }
      return $dom->saveHTML();
    }

    private function addCrawlerQueue($url, $domain_id = 0) {
        $r = $this->db->fetchAssoc("SELECT id FROM crawl_queue WHERE url='".$url."' AND domain_id=".$domain_id.";");
        if(count($r) == 0) {
            $sql = "INSERT INTO crawl_queue (url, added, domain_id) VALUES (:url, NOW(), :domid)";
            $q = $this->db->db->prepare($sql);
            $q->execute(array(':url'=>$url, ':domid'=>$domain_id));
        }
    }

    private function getSchemeID($scheme) {
      //var_dump($scheme);
      if(is_null($scheme)) return -1;
      //if($scheme === 'http') return 0;
      //if($scheme === 'https') return 1;
      //        var_dump($scheme);
      $scheme = trim($scheme);
      $sql = "SELECT id,name FROM protocols WHERE name LIKE '" . $scheme . "';";
      $r = $this->db->fetchAssoc($sql);
      //var_dump($r);
      if( count($r)===0 ) {
        $sql = "INSERT INTO protocols (name) VALUES (:scheme)";
        $q = $this->db->db->prepare($sql);
        $q->execute(array(':scheme'=>$scheme));
        //throw new Exception("Scheme dont exitst.... creating... try again");
        return $this->db->db->lastInsertId();
      }
      else {
        return $r[0]['id'];
      }
    }

    private function getDomainID($domain) {
      $d = new Domain($domain);
      return $d->getID();
    }

    private function getCTID($ct) {
        $sql = "SELECT id,value FROM content_type WHERE value like '%$ct%';";
        $r = $this->db->fetchAssoc($sql);
        if(count($r)===0) {
            $sql = "INSERT INTO content_type (value) VALUES (:ct)";
            $q = $this->db->db->prepare($sql);
            $q->execute(array(':ct'=>$ct));
            // php can return the last insert_id somehow.
            $sql = "SELECT id,value FROM content_type WHERE value like '%$ct%';";
            $r = $this->db->fetchAssoc($sql);
            return $r[0]['id'];
//            throw new Exception("Domain dont exitst.... creating... try again");
        }
        else {
            return $r[0]['id'];
        }
    }

    private function rmkdir($path, $mode = 0777) {
        $dirs = explode(DIRECTORY_SEPARATOR , $path);
        //       var_dump($dirs);
        $count = count($dirs);
        $path = '.';
        for ($i = 0; $i < $count; ++$i) {
            $path .= DIRECTORY_SEPARATOR . $dirs[$i];
            if (!is_dir($path)) {
//                echo "Creating dir : ";
//                var_dump($path);
                if(!mkdir($path, $mode)) {
                    return false;
                }
                //return false;
            }
        }
        return true;
    }

    private function dlStatus($msg) {
        $this->msgStack->push("&nbsp;<b><small>".mb_strtoupper($msg)."</small></b><br />");
    }

    private function showStatus() {
        while($this->msgStack->valid()) {
            echo $this->msgStack->current();
            $this->msgStack->next();
        }
    }

    private function format_size($size) {
        $mod = 1024;
        $units = explode(' ','B KB MB GB TB PB');
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}