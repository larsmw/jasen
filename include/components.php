<?php

include_once('logger.php');

interface Components {
}

/**
 * use diferent interfaces for ajax, html or cron 'output' ?
 */

class Component implements Components {


  public function __construct() {
  }

  public function register($namespace, $event, $method) {
    Events::register($namespace, $event, $method);
  }

  public function msg($message = NULL, $type = 'status') {
    Messages::set($message, $type);
  }
}

class Messages {
  public function render($type = NULL) {
    $all_msg = self::get_messages();
    $out = "";
    $count = 0;
    if(count($all_msg)>0) {
      $out = "<div id=\"message_box\">";
      foreach( $all_msg as $type) {
	foreach($type as $msg) {
	  //var_dump($msg);
	  $out .= $msg;
	  $count++;
	}
      }
      $out .= "</div>";
    }
    if($count>0)
      return $out;
    else
      return "";
  }

  public function set($message = NULL, $type = 'status') {
    if (!isset($_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type] = array();
    }
    if (!in_array($message, $_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type][] = $message;
    }
    return isset($_SESSION['messages']) ? $_SESSION['messages'] : NULL;
  }

  public function get_messages($type = 'status', $clear_queue = TRUE) {
    if ($messages = self::set()) {
      if ($type) {
	if ($clear_queue) {
	  unset($_SESSION['messages'][$type]);
	}
	if (isset($messages[$type])) {
	  return array($type => $messages[$type]);
	}
      }
      else {
	if ($clear_queue) {
	  unset($_SESSION['messages']);
	}
	return $messages;
      }
    }
    return array();
  }

}