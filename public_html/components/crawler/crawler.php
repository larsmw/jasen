<?php
include_once(ROOT . 'include/logger.php');
include_once(ROOT . 'components/crawler/uri.php');
include_once(ROOT . 'components/crawler/robots.txt.php');

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

    if (!$this->d->tableExists("link_text")) {
      $sql = "CREATE TABLE link_text ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
           "name VARCHAR(4096));";
      $this->d->exec($sql);
    }
    if (!$this->d->tableExists("link")) {
      $sql = "CREATE TABLE link ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
           "src INT NOT NULL, dst INT NOT NULL, link_text INT, last_visit TIMESTAMP);";
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
    $sql = "SELECT count(*) as num FROM crawl_queue;";
    $r = $this->d->q($sql);
    $o .= "<p>Queue size : " . $r[0]['num'] . "</p>";
    $o .= "<p>Dato : " . date("c") . "</p>";

    $sql = "SELECT * FROM cron_log order by last_run desc limit 25;";
    $r = $this->d->q($sql);
    $o .= "<dl>";
    foreach($r as $row) {
      $o .= "<dt>" . $row['source'] . "</dt>";
      $o .= "<dd>" . $row['message'] . "</dd>";
      $o .= "<dd>" . $row['last_run'] . "</dd>";
    }
    $o .= "</dl>";
    return array('content' => $o);
  }

  public function cron() {
    $num_crawl_queue_entries = 1000;
    //$this->d->q("DELETE FROM crawl_queue WHERE id NOT IN (SELECT id FROM ( select id from crawl_queue order by id desc limit " .$num_crawl_queue_entries. " ) foo );");
    $this->process(5);
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
  private function process($num_urls=10) {
    echo "START\n";
    $arr_http_code_ok = array(200);
    $good_content_types = array(
				"text/html",
				"text/html; charset=utf-8",
				"text/html;charset=utf-8",
				"text/html; charset=iso-8859-1",
				"text/html;charset=iso-8859-1",
				);
    $uri_list = $this->getUriList($num_urls);
    //var_dump($uri_list, __LINE__);
    //var_dump($num_urls, __LINE__);
    if (is_null($uri_list)) {
      echo "ERRORRRR -- list of urls to crawl is empty.\n";
      return;
    }

    foreach($uri_list as $k=>$a_uri) {
      if (is_array($a_uri)) {
	//var_dump($a_uri);
	$uri = Uri::loadById($a_uri['url_id']);
      } else {
	echo __LINE__;
	$uri = Uri::loadById($a_uri);
      }
      //echo __LINE__;
      //var_dump($uri);
      //var_dump($uri->getHost());
      //$this->log->add(var_export($uri, TRUE));

      $this->robots = new RobotsTxt($uri->getHost());

      if (!$this->robots->isBlocked($uri->getPath())) {
	if (!$this->robots->isTime()) {
	  // too early - put it back in the queue
	  $uri->addtoqueue();
	  continue;
	}
	$this->log->add("I Will crawl : <a href=" . $uri->toString() . " target=\"_BLANK\">" . $uri->toString() . "</a>");
	$response = $uri->fetch();
	echo "Crawling : ".$uri->toString()." ";
	echo "  - http:".$response['http_code']." ";
	

	if (!in_array(strtolower(trim($uri->getContentType())), $good_content_types)) {
	  echo __LINE__ . " " . $uri->toString() . " wrong content-type : ";
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
	    //var_dump($link, __LINE__);
	    //var_dump($uri->toString(), __LINE__);
	    if (is_string($link)) {
	      echo "SHOULD NOT HAPPEN!!!\n\n";
	      var_dump($link);
	      $u = new Uri($link, $uri->toString());
	    }
	    else {
	      $url = $link->getAttribute('href');
	      $text = trim($link->textContent);


	      $sql = "SELECT id,name FROM link_text WHERE name like '$text';";
	      $r = $this->d->q($sql);
	      $text_id = -1;
	      if(count($r)===0) {
		$sql = "INSERT INTO link_text (name) VALUES ('".$text."');";
		$text_id = $this->d->insert($sql);
	      }
	      else {
		$text_id = $r[0]['id'];
	      }
	      //var_dump($text_id);
	      //var_dump($uri->getId());



	      $u = new Uri($link->getAttribute('href'), $uri->toString());
	      //var_dump($u->getId());
	      $sql = "INSERT INTO link (src,dst,link_text,last_visit) VALUES (".
		$uri->getId().", ".$u->getId().", ".$text_id.", NOW());";
	      $link_id = $this->d->insert($sql);
	    }
	    $u->addtoqueue();
	  }
	  Events::trigger('content', 'index', 
			  ['content' => $response['content'], 'uri' => $uri ]);
	  $a = $DOM->getElementsByTagName('img');
	  //get images...
	  foreach($a as $img){
	    //var_dump($img, __LINE__);
	    //var_dump($img->getAttribute('src'));
	    $img_path = $img->getAttribute('src');
	    // parse the path into its constituent parts
	    $url_info = parse_url($img_path);
	    
	    // if the host part (or indeed any part other than "path") is set,
	    // then we're dealing with a fully qualified URL (or possibly an error)
	    if (!isset($url_info['host'])) {
	      // otherwise, get the relative path
	      $path = $url_info['path'];
	      
	      // and ensure it begins with a slash
	      if (substr($path,0,1) !== '/') $path = '/'.$path;
	      
	      // concatenate the site directory with the relative path
	      $img_path = $path;
	      if (substr($img_path,0,2) == "//") {
		$img_path = "http:" . $img_path;
	      }
	    }
	    file_put_contents("files/images/".$uri->getId()."-".basename($img_path),
			      file_get_contents($img_path));
	    //var_dump(basename($img_path));  // this should be a full URL 
	  }
	}
	else {
	  //var_dump($response['http_code'], __LINE__);
	}
      }
      else {
	// url is blocked by robots!?
	$this->log->add("This is blocked : <a href=" . $uri->toString() . ">" . $uri->toString() . "</a>");
	echo " Blocked! : " . $uri->toString();
      }
      //var_dump($uri->toString());
      echo "\n";
    }
    echo "DONE\n";
  }

  private function getUriList($count = 5) {
    $delay = 1800;
    $return = array();
    //var_dump($count, __LINE__);
    $sql = "select q.id, q.url_id from crawl_queue q WHERE q.priority=2 group by q.did order by time_to_crawl asc limit " . $count . ";";
    $r = $this->d->q($sql);
    //var_dump($r);
    foreach($r as $url) {
      //var_dump($url, __LINE__);
      $d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
    }
    if (empty($r) || (count($r)<$count) ) {
      $count = $count - count($r);
      //var_dump($count, "count");
      $sql = "select q.id, q.url_id from crawl_queue q WHERE q.priority=1 group by q.did order by time_to_crawl asc limit " . $count . ";";
      $r = $this->d->q($sql);
      //var_dump($r, __LINE__);
      $return = array_merge($return, $r);
      foreach($r as $url) {
	//var_dump($url, __LINE__);
	$d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
      }
      if (empty($r) || (count($r)<$count) ) {
	$count = $count - count($r);
	//var_dump($count, "count");
	$sql = "select q.id, q.url_id from crawl_queue q WHERE q.priority=0 group by q.did order by time_to_crawl asc limit " . $count . ";";
	$r = $this->d->q($sql);
	//var_dump($r, __LINE__);
      $return = array_merge($return, $r);
	foreach($r as $url) {
	  //var_dump($url, __LINE__);
	  $d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
	}
	if (empty($r) || (count($r)<$count) ) {
	  $count = $count - count($r);
	  //var_dump($count, "count");
	  $sql = "select q.id as url_id, q.domain_id from crawl_queue q group by q.domain_id order by q.id asc limit " . $count . ";";
	  $r = $this->d->q($sql);
	  //$this->log->add("Could not get " . $count . " items to crawl...");
	  //$this->log->add("Loaded :  " . var_export($r, TRUE));
      $return = array_merge($return, $r);
	  foreach($r as $url) {
	    //var_dump($url, __LINE__);
	    $d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
	  }
	} else {
      $return = array_merge($return, $r);
	  foreach($r as $url) {
	    //var_dump($url, __LINE__);
	    $d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
	    //var_dump($d);
	  }
	  //echo "prio=0\n";
	}
      } else {
      $return = array_merge($return, $r);
	foreach($r as $url) {
	  //var_dump($url, __LINE__);
	  $d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
	  //var_dump($d);
	}
	//echo "prio=1\n";
	return $r;
	}
    } else {
      $return = array_merge($return, $r);
      foreach($r as $url) {
	//var_dump($url, __LINE__);
	$d = $this->d->q("delete from crawl_queue where url_id=" . $url['url_id'] . ";");
	//var_dump($d);
      }
      //echo "prio=2\n";
    }
    return $return;
  }
}

