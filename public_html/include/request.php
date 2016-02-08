<?php

class Request {

  /**
   * @return : the path for the request - minus type argument
   */
  public function path($index=NULL) {
    $types = array('html', 'cron', 'ajax');
    $path = (isset($_GET['q']))?explode("/", $_GET['q']):array("html");
    if (in_array($path[0], $types)) {
      array_shift($path);
    }
    if (!is_null($index)) {
      return $path[$index-1];
    }
    else {
      return implode('/', $path);
    }
  }
}
