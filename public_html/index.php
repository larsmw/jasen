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

require_once '../libs/vendors/autoload.php';
require_once 'core/core.php';

class App implements Plugin {

  //public function init() {
  //}

  public function run( $data ) {
    $u = new \User();

    if(!$u->loggedin()) {
      $u->loginform("form_user_login", "form_user_login_id", "/");
    }
    else {
      $u->logout_btn();
    }
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
