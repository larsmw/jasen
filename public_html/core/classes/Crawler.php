<?php

namespace Crawler;

require_once "Database.php";
require_once "Robots.php";
require_once "Interfaces.php";

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

    $sql = "SELECT id,name FROM domain WHERE name like '" . $this->name . "';";
    $r = $this->db->fetchAssoc($sql);
    if(count($r)===0) {
      $sql = "INSERT INTO domain (name, next_visit) VALUES (:domain, NOW())";
      $q = $this->db->db->prepare($sql);
      $q->execute(array(':domain'=>$this->name));
      $sql = "SELECT id,name FROM domain WHERE name like '" . $this->name . "';";
      $r = $this->db->fetchAssoc($sql);
      //            var_dump($r);
      $this->id = $r[0]['id'];
      return $this->id;
      //            throw new Exception("Domain dont exitst.... creating... try again");
    }
    else {
      return $r[0]['id'];
    }
  }
}

class Url {
  private $domain,$parts,$url;

  public function __construct($url) {
    $this->url = $url;
    $this->domain = new Domain($url);
    $this->parts = parse_url($url);
  }

  public function valid() {
    return (!empty($this->parts['host'])) && (filter_var($this->url, FILTER_VALIDATE_URL) == TRUE);
  }
}

class Crawler implements \Plugin {

    protected $db;

    private static $std_crawldelay = 600;
    private $msgStack;

    public function __construct()
    {
      date_default_timezone_set("Europe/Copenhagen");
      $this->db = new \Database();
      $this->msgStack = new \SplStack();
    }

    protected function __clone()
    {
      //Me not like clones! Me smash clones!
    }

    /*    public static function getInstance()
    {
        global $databases;
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$db = \Database::getInstance();
        }
        return self::$_instance;
	}*/

    public function getReport() {
        $r = $this->db->fetchAssoc("SELECT count(*) as num FROM urls;");
        $s = "<h3>Stats</h3>";
        $s .= "Links : ".$r[0]['num']."<br />";
        $r = $this->db->fetchAssoc("SELECT count(*) as num FROM domain;");
        $s .= "Domains : ".$r[0]['num']."<br />";
        return $s;
    }

    /*
     * Starts a run of the crawler.
     */
    public function run() {
      if(isset($_GET['q'])) {
        if($_GET['q'] === 'crawl' ) {
            $this->doRun();
        }
      }


/*        $pid = pcntl_fork(); // fork
        if ($pid < 0)
            exit;
        else if ($pid) // parent
            exit;
        else { // child
            $sid = posix_setsid();

            if ($sid < 0)
                exit;

            $this->doRun();
        }

        header('Location: /');*/
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
        ob_start();
        echo "<html><head>";
                echo "<meta http-equiv=\"refresh\" content=\"5\">";
        echo "</head><body>";
        echo date(DATE_ATOM)."<br />\n";

        $sql = "SELECT id FROM `domain` WHERE next_visit<NOW() order by next_visit ASC limit " . $numUrls . ";";
        $ids = $this->db->fetchAssoc($sql);
        //var_dump($ids);
        if(empty($ids)) return;
        foreach($ids as $id) {
            $db_ids[] = intval($id['id']);
        }
//        var_dump(array_values($db_ids));
        
//        $sql = "SELECT c.id,c.url FROM crawl_queue c WHERE domain_id = '".$db_id."' order by id ASC limit 1;";
//        $urls = $this->db->fetchAssoc($sql);
//        var_dump($urls,$sql);


//        $sql = "SELECT c.id,c.url,d.next_visit FROM crawl_queue c inner join domain d on d.id=c.domain_id group by d.id order by d.next_visit asc limit ".$numUrls.";";
        $dl_total = 0;
        // For each url that should be crawled
//        die();
        foreach($db_ids as $db_id) {
            $sql = "SELECT c.id,c.url FROM crawl_queue c WHERE c.domain_id = '".$db_id."' order by c.id ASC limit 1;";
            $url = $this->db->fetchAssoc($sql);
            //var_dump($url,$sql);
            if(!count($url)) {
              // Crawl frontpage
              $url[0]['url'] = "";
            }
            $url = $url[0];
            $sql = "SELECT name FROM domain WHERE id=$db_id;";
            $r = $this->db->fetchAssoc($sql);
            //var_dump($r,$url);
            $url['url'] = "http://".rtrim($r[0]['name'], "//")."/".ltrim($url['url'], "/");
            echo "<b>Crawling : </b>" . $this->limit_text($url['url'], 70)."";
            ob_flush();

            $url_o = new Url($url['url']);

            $url_part = parse_url($url['url']);
            
            if(!$url_o->valid()) {
                echo "<b>Invalid url</b><br />";

                // remove url from crawl_queue
                $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
                $q = $this->db->db->prepare($sql);
                $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
                $q->execute();
                continue;
            }

            if(!isset($url_part['path'])) $url_part['path'] = "/";

            $this->updateNextVisit($url['url']);

            // delete domain if wrong
            if($db_id != $this->getDomainID($url['url'])) {
              var_dump($db_id);
              $sql = "DELETE FROM domain WHERE id = :cid;";
              $q = $this->db->db->prepare($sql);
              $q->BindParam('cid', $db_id, \PDO::PARAM_INT);
              $q->execute();
            }

            try {
                $robot = new \robotstxt($url_part['scheme']."://".$url_part['host']);
            }
            catch( Exception $e ) {
            }
            if($robot->isUrlBlocked($url['url'])) {
                echo "Blocked by robots<br />";
                // remove url from crawl_queue
                $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
                $q = $this->db->db->prepare($sql);
                $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
                $q->execute();
                continue;
            }

            $headers = get_headers($url['url'],1);
            //var_dump($headers[0]);
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
                echo "&nbsp;<b>" . $headers[0] ."</b>";
                //var_dump($headers['Location']);
                if(is_array($headers['Location'])) {
                  $location = $headers['Location'][0];
                }
                else
                {
                  $location = $headers['Location'];
                }
                echo " -> " . $location . "<br />";
                $tmp = parse_url($location);
                $base = parse_url($url['url']);
                if(empty($tmp['host'])) $tmp['host'] = $base['host'];
                if(empty($tmp['scheme'])) $tmp['scheme'] = $base['scheme'];
                $tmp['scheme'] = $this->getSchemeID($tmp['scheme']);
                $tmp['host'] = $this->getDomainID($tmp['host']);
                if(!isset($tmp['path'])) $tmp['path'] = '/';
                $ret[] = $tmp;
                //var_dump($tmp);
                $this->addCrawlerQueue($tmp['path'], $tmp['host']);
                $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
                $q = $this->db->db->prepare($sql);
                $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
                $q->execute();
                $run = false;
                break;
              case 'HTTP/1.1 500 Internal Server Error' : 
              case 'HTTP/1.1 400 Bad Request' :
              case 'HTTP/1.0 403 Forbidden' :
              case 'HTTP/1.1 403 Forbidden' :
                // Wrong - remove link
                $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
                $q = $this->db->db->prepare($sql);
                $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
                $q->execute();
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
                case "text/html;charset=ISO-8859-1" :
                  $response = $this->downloadUrl($url['url']);
                  //var_dump($response);
                  // fetch urls from response
                  $dl_length = strlen($response);
                  echo " - 1 Downloaded <b>".$this->format_size($dl_length)."</b> bytes.<br />";
                  $dl_total += $dl_length;
                  
                  $urls = $this->pageLinks($response, $url['url'], true);
                  //                var_dump($urls);
                  //echo count($links);
                  break;
                default:
                  //                echo var_export($contenttype, TRUE)." : ";
                  var_dump($contenttype);
                  /*                  $content = $this->downloadUrl($url['url']);
                  $dl_length = strlen($content);
                  echo " - 2 Downloaded <b>".$this->format_size($dl_length)."</b> bytes.<br />";
                  $dl_total += $dl_length;
                  
                  $finfo = new \finfo(FILEINFO_MIME);
                  $this->dlStatus($finfo->buffer($content));
                  if($finfo->buffer($content) == "application/x-empty") {
                    echo "No content!";
                    // remove url from crawl_queue
                    $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
                    $q = $this->db->db->prepare($sql);
                    $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
                    $q->execute();
                    continue;
                  }
                  
                  var_dump($url);
                  //var_dump($content);
                  $urls = $this->pageLinks($content, $url['url'], true);
                  var_dump(count($urls));*/
                  
                  break;
              }
              
              // remove url from crawl_queue
              $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
              $q = $this->db->db->prepare($sql);
              $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
              $q->execute();
            }
            $this->showStatus();
        }
        echo "<p>Totally downloaded : <b>".$this->format_size($dl_total)."</b>.</p>";
        echo xdebug_memory_usage()." bytes memory<br />";
        echo xdebug_time_index()." seconds";
        echo "</body></html>";
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

    private function getUrlID($url) {
        $tmp = parse_url($url);
        if(empty($tmp['host'])) $tmp['host'] = $base['host'];
//        var_dump($url);
        $tmp['scheme'] = $this->getSchemeID($tmp['scheme']);
        $tmp['host'] = $this->getDomainID($tmp['host']);
        if(!isset($tmp['path'])) $tmp['path'] = '/';
        $ret[] = $tmp;

        $r = $this->db->fetchAssoc("SELECT id FROM urls WHERE url='".$tmp['path'].
                                   "' AND domain_id=".$tmp['host'].
                                   " AND scheme_id=".$tmp['scheme'].
                                   ";");
        if(count($r)) {
            return $r[0]['id'];
        }
        else
        {
            return NULL;
        }
    }

    private function updateNextVisit($domainName, $seconds = 30) {
        $dID = $this->getDomainID(parse_url($domainName)['host']);
        //var_dump($dID);
        $this->updateNextVisitDomainID($dID, $seconds);
    }

    private function updateNextVisitDomainID($dID, $seconds = 30) {
        $sql = "UPDATE domain SET next_visit=DATE_ADD(NOW(), INTERVAL :sec SECOND) WHERE id=:id;";
        $q = $this->db->db->prepare($sql);
        $q->execute(array(':id'=>$dID, ':sec'=>$seconds));
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