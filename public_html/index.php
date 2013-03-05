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

ini_set('display_errors', 'FALSE');
ini_set('html_errors', 'FALSE');
ini_set('log_errors', 'TRUE');
//xdebug_enable();
//xdebug_start_code_coverage();

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

define("ROOT", getcwd());

require_once 'includes/classes/Application.php';

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
        session_start();
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
        if($cmd === "logout") {
            session_start();
            session_destroy();
            header("location:index.php");
        }
        $this->checkFormData();
        echo $this->parse();
    }

    private function checkFormData() {
        $host = "localhost"; // Host name
        $username = "linkhub"; // Mysql username
        $password = "was&87Bki"; // Mysql password
        $db_name = "linkhub"; // Database name
        $tbl_name = "users"; // Table name
// Connect to server and select databse.
        mysql_connect("$host", "$username", "$password") or die("cannot connect");
        mysql_select_db("$db_name") or die("cannot select DB");

        $count = 0;
        if (isset($_POST['myusername']) && isset($_POST['mypassword'])) {
// username and password sent from form
            $myusername = $_POST['myusername'];
            $mypassword = $_POST['mypassword'];

// To protect MySQL injection (more detail about MySQL injection)
            $myusername = stripslashes($myusername);
            $mypassword = stripslashes($mypassword);
            $myusername = mysql_real_escape_string($myusername);
            $mypassword = mysql_real_escape_string($mypassword);

            $sql = "SELECT * FROM $tbl_name WHERE username='$myusername' and password=PASSWORD('$mypassword');";
            $result = mysql_query($sql);

// Mysql_num_row is counting table row
            $count = mysql_num_rows($result);
// If result matched $myusername and $mypassword, table row must be 1 row

            if ($count == 1) {

// Register $myusername, $mypassword and redirect to file "login_success.php"
                session_register("myusername");
                session_register("mypassword");
                header("location:login_success.php");
            } else {
                echo "Wrong Username or Password";
            }
        }
    }

    private function parse() {
      $template = file_get_contents("templates/index.html");
      if(!session_is_registered(myusername)){
          $login_form = file_get_contents("templates/login-form.html");
      }
      else {
          $login_form = "Velkommen!";
      }
      $template = str_replace("{%region:mainmenu}", $this->menu(), $template);
      $template = str_replace("{%login-form}", $login_form, $template);
      return $template;
    }

    public function menu() {
        // check access rights
        parent::showMenu(0);
    }

}

$t = new test();
?>
