<?php

require_once ROOT.'/includes/classes/Interfaces.php';
require_once ROOT.'/includes/classes/Base.php';


class User extends Base implements IWebObject {

  public function onRun( $sender, $args ) {
      //var_dump($sender); var_dump($args);
  }

  public function onMenu( &$menu ) {
      //var_dump($menu);
  }
}
