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


class AddUrl extends Database {
    public function __construct($form_id) {
        parent::__construct();
        var_dump($_POST);
    }

    public function get($name) {
        if(isset($_POST['url'])) {
            return $_POST['url'];
        }
        else
            {
                echo "Missing url";
            }
    }

    public function insert($url) {
        $sql = "INSERT INTO crawl_queue (url, added) VALUES (:url, NOW())";
        $q = $this->db->prepare($sql);
        $q->execute(array(':url' => $url));
    }
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
//        throw new UnknownException();

        if(isset($_GET['q'])){
            $cmd = $_GET['q'];
        }
        else {
            $cmd = "";
        }
        if($cmd === "crawl") {
            $Timer = 10; //seconds
            echo "this text is from index.php<br />";
            echo "Crawling... <br />" . microtime();
            die();
        }
        if($cmd === "addurl") {
            $f = new AddUrl('add_url');
            $f->insert($f->get('url'));
        }
        parent::__construct();
        if($cmd === "logout") {
            unset($_COOKIE['myusername']);
            session_start();
            session_unset();
            session_destroy();
            header("location:/");
        }
        $this->checkFormData();
        echo $this->parse();
    }

    /**
     * Check if user login details is correct. Set cookies.
     */
    private function checkFormData() {
        $tbl_name = "users"; // Table name

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
            $result = $this->db->fetchAssoc($sql);

// Mysql_num_row is counting table row
            $count = count($result);
// If result matched $myusername and $mypassword, table row must be 1 row

            if ($count == 1) {

// Register $myusername, $mypassword and redirect to file "login_success.php"
                setcookie("myusername", $myusername, time() + 7200);
                setcookie("mypassword", crypt($mypassword), time() + 7200);
                header("location:index.php");
            } else {
                echo "Wrong Username or Password";
            }
        }
    }

    private function parse() {
      $template = file_get_contents("templates/index.html");
      
      if(empty($_COOKIE['myusername'])){
          $login_form = file_get_contents("templates/login-form.html");
      }
      else {
          $login_form = "Velkommen ".$_COOKIE['myusername']."!";
      }
      $template = str_replace("{%region:mainmenu}", $this->menu(), $template);
      $template = str_replace("{%login-form}", $login_form, $template);
      
      return $template;
    }

    public function menu() {
        // check access rights
        return parent::showMenu(0);
    }

}

$t = new test();


?>
