<?php

namespace Crawler;

require_once "Database.php";
require_once "Robots.php";
require_once "Interfaces.php";

class Crawler extends \interfaces\Singleton {

    private $db;

    public function __construct() {
        $this->db = \Database::getInstance();
    }

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
        $this->doRun();
        die();
        
        $pid = pcntl_fork(); // fork
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

        header('Location: /');
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
        echo "<html><head>";
        echo "<meta http-equiv=\"refresh\" content=\"2\">";
        echo "</head><body>";
        echo date(DATE_ATOM)."<br />\n";
        $sql = "SELECT * FROM crawl_queue order by added asc limit ".$numUrls.";";
        $urls = $this->db->fetchAssoc($sql);
        // For each url that should be crawled
        foreach($urls as $url) {
            echo $url['url']."<br />";
            $url_part = parse_url($url['url']);
            //var_dump($url_part);
            if(empty($url_part['host'])) {
                echo "<b>Invalid url</b><br />";
                
                // remove url from crawl_queue
                $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
                $q = $this->db->db->prepare($sql);
                $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
                $q->execute();continue;
            }
            // check if time is up for next visit...
            $sql = "SELECT name,next_visit FROM domain WHERE name='".$url_part['host']."' AND next_visit < NOW();";
            $r = $this->db->fetchAssoc($sql);
            //echo $sql;
            //echo "<b>TIMESUP!!!</b>";
            //var_dump($r);
            if(empty($r)) {
                echo "<b><small>NOT YET</small></b><br />"; // continue to next url
                continue;
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

            $contenttype = get_headers($url['url'],1)['Content-Type'];
//            var_dump($contenttype);
            if($contenttype === "text/html") {

                $response = $this->downloadUrl($url['url']);
                //var_dump($response);
                // fetch urls from response
                echo "Downloaded ".strlen($response)." bytes.<br />";
                
                $fn = $url_part['host'].$url_part['path'];
                $dn = dirname($fn);
                $bn = basename($fn);
                var_dump($dn);
                var_dump($bn);
                $this->rmkdir("files/".$dn);
                echo basename($fn)."<br />";
                file_put_contents("files/".$dn."/".$bn."".urlencode($url_part['query']), $response);

                $this->updateNextVisit($url['url']);
                // Gets links from the page and formats them to a full valid url:
                echo "urls in page : ";
                $urls = $this->pageLinks($response, $url['url'], true);
//                var_dump($urls);
                //echo count($links);
            }
            else {
//                echo var_export($contenttype, TRUE)." : ";
                $content = $this->downloadUrl($url['url']);

                $finfo = new \finfo(FILEINFO_MIME);
                echo $finfo->buffer($content) . "\n";
                if($finfo->buffer($content) == "application/x-empty") {
                    echo "No content!";
                    // remove url from crawl_queue
                    $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
                    $q = $this->db->db->prepare($sql);
                    $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
                    $q->execute();
                    break;
                }

                $fn = $url_part['host'].$url_part['path'];
                $dn = dirname($fn);
                $bn = basename($fn);
                var_dump($bn);
                $this->rmkdir("files/".$dn);
                echo basename($fn)."<br />";
                file_put_contents("files/".$dn."/".$bn, $content);
                // remove url from crawl_queue
                var_dump($url['id']);
                $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
                $q = $this->db->db->prepare($sql);
                $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
                $q->execute();
            }

            // remove url from crawl_queue
            $sql = "DELETE FROM crawl_queue WHERE id = :cid;";
            $q = $this->db->db->prepare($sql);
            $q->BindParam('cid', $url['id'], \PDO::PARAM_INT);
            $q->execute();
        }
        echo "</body></html>";
    }

    // $html        = the html on the page
// $current_url = the full url that the html came from (only needed for $repath)
// $repath      = converts ../ and / and // urls to full valid urls
    function pageLinks($html, $current_url = "", $repath = false){
        preg_match_all("/\<a.+?href=(\"|')(?!javascript:|#)(.+?)(\"|')/i", $html, $matches);
        $links = array();
        $ret = array();
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
                $tmp = parse_url($link);
                if(empty($tmp['host'])) $tmp['host'] = $base['host'];
                if(empty($tmp['scheme'])) $tmp['scheme'] = $base['scheme'];
//                var_dump($link);
                $tmp['scheme'] = $this->getSchemeID($tmp['scheme']);
                $tmp['host'] = $this->getDomainID($tmp['host']);
                if(!isset($tmp['path'])) $tmp['path'] = '/';
                $ret[] = $tmp;
                $this->addCrawlerQueue($link, $tmp['host']);

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
//        var_dump($ret);
        return $ret;
    }

    private function getUrlID($url) {
        $tmp = parse_url($url);
        if(empty($tmp['host'])) $tmp['host'] = $base['host'];
        var_dump($url);
        $tmp['scheme'] = $this->getSchemeID($tmp['scheme']);
        $tmp['host'] = $this->getDomainID($tmp['host']);
        if(!isset($tmp['path'])) $tmp['path'] = '/';
        $ret[] = $tmp;
        
        $r = $this->db->fetchAssoc("SELECT id FROM urls WHERE url='".$tmp['path'].
                                   "' AND domain_id=".$tmp['host'].
                                   " AND scheme_id=".$tmp['scheme'].
                                   ";");
    }

    private function updateNextVisit($domainName, $seconds = 5) {
        $dID = $this->getDomainID(parse_url($domainName)['host']);
        $sql = "UPDATE domain SET next_visit=DATE_ADD(NOW(), INTERVAL :sec SECOND) WHERE id=:id;";
        $q = $this->db->db->prepare($sql);
        $q->execute(array(':id'=>$dID, ':sec'=>$seconds));
    }

    private function addCrawlerQueue($url, $domain_id = 0) {
        $sql = "INSERT INTO crawl_queue (url, added, domain_id) VALUES (:url, NOW(), :domid)";
        $q = $this->db->db->prepare($sql);
        $q->execute(array(':url'=>$url, ':domid'=>$domain_id));
    }

    private function getSchemeID($scheme) {
        if($scheme === 'http') return 0;
        if($scheme === 'https') return 1;
        var_dump($scheme);
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

    private function getDomainID($domain) {
        $sql = "SELECT id,name FROM domain WHERE name like '%$domain%';";
        $r = $this->db->fetchAssoc($sql);
        if(count($r)===0) {
            $sql = "INSERT INTO domain (name) VALUES (:domain)";
            $q = $this->db->db->prepare($sql);
            $q->execute(array(':domain'=>$domain));
            $sql = "SELECT id,name FROM domain WHERE name like '%$domain%';";
            $r = $this->db->fetchAssoc($sql);
            return $r[0]['id'];
//            throw new Exception("Domain dont exitst.... creating... try again");
        }
        else {
            return $r[0]['id'];
        }
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
        var_dump($dirs);
        $count = count($dirs);
        $path = '.';
        for ($i = 0; $i < $count; ++$i) {
            $path .= DIRECTORY_SEPARATOR . $dirs[$i];
            if (!is_dir($path)) {
                var_dump($path);
                if(!mkdir($path, $mode)) {
                    return false;
                }
                //return false;
            }
        }
        return true;
    }
}