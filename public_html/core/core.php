<?php
/**
 * Linkhub
 *
 * This is a project....
 */

include_once("classes/Interfaces.php");

require_once("Config.php");

class EventDispatcher {
  private $map,$obejcts;

  function addListener($arg1, $arg2 = null) {
    if(!isset($this->objects[$arg1])) {
      $this->objects[$arg1] = new $arg1;
    }
    foreach(get_class_methods($arg1) as $method) {
      $this->map[$method][] = $arg1;
    }
  }
  
  function invoke($eventName, $data = null) {
    //var_dump($this->map);
    if(isset($this->map[$eventName])) {
      foreach ($this->map[$eventName] as $callback) {
	    call_user_func_array(array($this->objects[$callback], $eventName), array($data));
      }
    }
  }
}

class Core {

  public function __construct() {

    // TODO: make an autoloader
    session_start();

    // Find plugins
    $files = scandir(ROOT."/core/classes");

    foreach($files as $file) {
      if(preg_match("/^.*\.(inc|php)$/i", $file)) {
        include_once(ROOT."/core/classes/".$file);
      }
    }
    $d = new EventDispatcher();
    foreach(get_declared_classes() as $c) {
      if(in_array('Plugin', class_implements($c))) {
        $d->addListener($c);
      }
    }

    $user = new User();
    $d->invoke("init");
    $d->invoke("run", $user);
    $d->invoke("destroy");
  }
}
