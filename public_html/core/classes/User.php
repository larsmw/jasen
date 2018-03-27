<?php

namespace App;

require_once ROOT.'/core/classes/Interfaces.php';
require_once ROOT.'/core/classes/Base.php';

class User extends Base implements \interfaces\IWebObject {

  public function run( $sender, $args ) {
      //var_dump($sender); var_dump($args);
  }

  public function onMenu( &$menu ) {
      //var_dump($menu);
      $menu[] = array(  'Forside',
                        '<front>');
  }

  /**
   * Check if user login details is correct. Set cookies.
   */
  private function validateLogin() {
    $tbl_name = "users"; // Table name

    $count = 0;
    if (isset($_POST['myusername']) && isset($_POST['mypassword'])) {
      // username and password sent from form
      $myusername = $_POST['myusername'];
      $mypassword = $_POST['mypassword'];

      // To protect MySQL injection (more detail about MySQL injection)
      $myusername = stripslashes($myusername);
      $mypassword = stripslashes($mypassword);
      $myusername = \mysql_real_escape_string($myusername);
      $mypassword = \mysql_real_escape_string($mypassword);

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
}
