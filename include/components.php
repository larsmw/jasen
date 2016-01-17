<?php

include_once('logger.php');

interface Components {
}

/**
 * use diferent interfaces for ajax, html or cron 'output' ?
 */

class Component implements Components {

  public function register($namespace, $event, $method) {
    Events::register($namespace, $event, $method);
  }

}
