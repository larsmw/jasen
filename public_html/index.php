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
//xdebug_enable();
//xdebug_start_code_coverage();

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

define("ROOT", getcwd());

require_once 'includes/base.php';

/**
 * Handle errors properly
 */
class UnknownException extends Exception {
    
}




/**
 * Test class to prove we are right.
 */
class test extends Application {

    /**
     * Call constructor of parent.
     */
    public function __construct() {
        parent::__construct();
//        throw new UnknownException();
        if(isset($_GET['q'])){
            $cmd = $_GET['q'];
        }
        else {
            $cmd = "";
        }
        if($cmd === "crawl") {
            $Timer = 10; //seconds
            echo "this text is from simple.php";
            echo "Crawling... " . microtime();
            die();
        }
	echo $this->parse();
    }

    private function parse() {
      $template = file_get_contents("templates/index.html");
      $template = str_replace("{%region:mainmenu}", $this->menu(), $template);
      return $template;
    }

    public function menu() {
	parent::showMenu(0);
    }

}

$t = new test();
?>
