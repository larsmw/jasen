<?php

require_once ROOT.'/core/classes/Interfaces.php';
require_once ROOT.'/core/classes/Base.php';


class User extends Base implements IWebPlugin {

  public function onRun( $sender, $args ) {
      echo "User object invoked...";
      var_dump($sender); var_dump($args);
  }

  public function onMenu( &$menu ) {
      //var_dump($menu);
  }
}
