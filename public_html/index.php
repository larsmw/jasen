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
    if(isset($_GET['q'])) {
      if($_GET['q'] === 'ajax_start_crawl' ) {
        require_once 'core/classes/Crawler.php';
        echo "stater crawl";
        $c = new Crawler\Crawler();
        $c->ajax_run();
        die();
      }
    }

    $u = new \User();

    if(!$u->loggedin()) {
      $u->loginform("form_user_login", "form_user_login_id", "/");
    }
    else {
      echo <<<EOL
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
<script type="text/javascript">
jQuery(function($) {
$('#ajax_run').click(
    function(){
      console.log("Hej");
        var id = $(this).attr('id');
        jQuery.ajax({
            url: "ajax_start_crawl",
            type: "POST",
	    success: function(res) {
              alert(res);
            }
        });
    });
  });
</script>
EOL;
      $u->logout_btn();
      echo '<button id="ajax_run" name="Run">Run</button>';
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
