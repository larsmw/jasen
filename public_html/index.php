<?php

/**
 * This is a search engine...
 *
 * @author Lars Nielsen <lars@lfweb.dk>
 */

/**
Routing ideas

  /MODULE/ID/METHOD

  eg.
  /page/23/view
  /search/the+string+to+find/list
  /admin/user/
  /admin/user/add
  /crawler/control/start
**/

ini_set('display_errors', 'TRUE');
ini_set('html_errors', 'TRUE');
ini_set('log_errors', 'TRUE');
xdebug_enable();
xdebug_start_code_coverage();

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

define("ROOT", getcwd());

require_once 'core/core.php';
// Death come here...

require_once 'core/classes/Application.php';
require_once 'core/classes/Crawler.php';
require_once 'core/classes/Pager.php';

class Router {
  private $route;

  public function add($r, callable $c) {
    $this->route[$r] = $c;
  }

  public function execute() {
    $path=isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'/';
    $this->r[$path]();
  }
}

class App implements Plugin {
    private $config;

    public function init() {
        $this->config = new Config();
    }

    public function run() {
//        var_dump($this->config);
        echo "App 1 starting...";
    }
}


// namespaces
use App,Crawler;

/**
 * Handle errors properly
 */
class UnknownException extends Exception {

}


class UrlQueue extends Database {
    public function __construct($form_id) {
        parent::__construct();
//        var_dump($_POST);
    }

    public function get($name) {
        if(isset($_POST['url'])) {
            return $_POST['url'];
        }
        else
            {
                throw new UnknownException("Missing url");
            }
    }

    public function add($url) {
        $sql = "INSERT INTO crawl_queue (url, added) VALUES (:url, NOW())";
        $q = $this->db->prepare($sql);
        $q->execute(array(':url' => $url));
    }
}


/**
 * Test class to prove we are right.
 */
class test extends \Application {

    /**
     * Start 
     */
    public function __construct() {
        session_start();

	$route = new Router();
	$router->add('/crawler', [new Crawler, 'doRun']);
	$router->add('/', 'parse');

	$router->execute();
    }

    /**
     * @args NONE
     */
    private function parse() {
      $template = file_get_contents("templates/index.html");

      if(empty($_COOKIE['myusername'])){
          $login_form = file_get_contents("templates/login-form.html");
      }
      else {
          $login_form = "Velkommen ".$_COOKIE['myusername']."!";
      }
      //$crawler = Crawler\Crawler::getInstance();
      $template = str_replace("{%region:mainmenu}", $this->menu(), $template);
      $template = str_replace("{%login-form}", $login_form, $template);
      //$template = str_replace("{%url-report}", $crawler->getReport(), $template);

      return $template;
    }

    public function menu() {
        // check access rights
        return parent::showMenu(0);
    }

}

//$t = new test();

$c = new Core();

?>
