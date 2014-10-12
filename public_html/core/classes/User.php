<?php

require_once ROOT.'/core/classes/Interfaces.php';

class User implements Plugin {

  public function init() {
      echo "User object init()...";
  }

  public function run() {
      echo "User object run...";
  }
}
