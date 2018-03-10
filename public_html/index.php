<?php
//
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
use App;

define("ROOT", getcwd());
include ROOT."/core/classes/Application.php";

/**
 * Handle errors properly
 */
class UnknownException extends \Exception {
    
}

/**
 * Implementation of Application class
 */
class myApp extends App\Application {

    /**
     * Call constructor of parent.
     */
    public function __construct() {
        session_start();

        if(isset($_GET['q'])){
            $cmd = $_GET['q'];
        }
        else {
            $cmd = "";
        }
        $cmd = explode("/", $cmd);

        if($cmd[0] === "crawl") {
            $crawler = App\Crawler::getInstance();
            $crawler->run();
            die();
        }
        if($cmd === "addurl") {
            $f = new Url('add_url');
            $f->insert($f->get('url'));
        }
        if($cmd[0] === "pager") {
            $p = new Pager();
            $p->run();
            die();
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
      $crawler = Crawler\Crawler::getInstance();
      $template = str_replace("{%region:mainmenu}", $this->menu(), $template);
      $template = str_replace("{%login-form}", $login_form, $template);
      $template = str_replace("{%url-report}", $crawler->getReport(), $template);
      
      return $template;
    }

    public function menu() {
        // check access rights
    }

}

try {
    $m = new myApp();
}
catch (Exception $e) {
    error_log($e);
    echo "Fejlstatus : ERROR (se mere i log-filen)\n";
}

