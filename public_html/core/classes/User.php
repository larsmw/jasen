<?php

require_once ROOT.'/core/classes/Interfaces.php';

class User {

  //table fields
  var $user_table = 'users';          //Users table name
  var $user_column = 'username';     //USERNAME column (value MUST be valid email)
  var $pass_column = 'password';      //PASSWORD column
  var $db;
  
  public function __construct() {
    $this->db = new \Database();
    if(isset($_POST['username'])) {
      //var_dump($_POST);
      $this->logincheck($_POST['password']);
      unset($_POST);
    }
    if(isset($_POST['logout_action'])) {
      $this->logout();
    }
  }

  public function run() {
    
  }

  public function access($permission) {
    // check if user has permission
    //var_dump($permission);
    if($this->loggedin())
      return TRUE;
    else
      return FALSE;
  }

  //check if loggedin
  public function logincheck($logincode, $user_table="", $pass_column="", $user_column=""){
    //make sure password column and table are set
    if($this->pass_column == ""){
      $this->pass_column = $pass_column;
    }
    if($this->user_column == ""){
      $this->user_column = $user_column;
    }
    if($this->user_table == ""){
      $this->user_table = $user_table;
    }
    //exectue query
    $result = $this->db->fetchAssoc("SELECT * FROM ".$this->user_table." WHERE ".$this->pass_column." = PASSWORD('".$logincode."');");
    var_dump($result);
    //return true if logged in and false if not
    if(count($result)) {
      // login succeeded 
      setcookie('loggedin', $result[0]['id']);
    }else{
      return false;
    }
  }

  public function loggedin() {
    return isset($_COOKIE['loggedin']);
  }

  public function logout() {
    unset($_COOKIE['loggedin']);
    return;
  }

  public function logout_btn() {
    return '
<form name="user_logout" method="post" id="user_logout_id" enctype="application/x-www-form-urlencoded" action="/">
<input name="logout_action" id="action" value="logout" type="hidden">
<input name="submit" id="submit" value="Logout" type="submit"></div>
</form>
';
  }

  public function loginform($formname, $formclass, $formaction){
    return '
<form name="'.$formname.'" method="post" id="'.$formname.'" class="'.$formclass.'" enctype="application/x-www-form-urlencoded" action="'.$formaction.'">
<div><label for="username">Username</label>
<input name="username" id="username" type="text"></div>
<div><label for="password">Password</label>
<input name="password" id="password" type="password"></div>
<input name="action" id="action" value="login" type="hidden">
<div>
<input name="submit" id="submit" value="Login" type="submit"></div>
</form>
 
';
  }
}
