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

//require_once 'core/classes/Application.php';
//require_once 'core/classes/Crawler.php';
//require_once 'core/classes/Pager.php';

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
//use App,Crawler;

/**
 * Handle errors properly
 */
class UnknownException extends Exception {

}

$c = new Core();

?>
