<?php

namespace LinkHub\Modules;

use LinkHub\Modules\Uri as Uri;
use LinkHub\Modules\Robots as Robots;

class Crawler implements \IPlugin {

  private $d;
  
  public function __construct() {
    $this->d = new \Database();
  }

  public function cron( $sender, $args ) {
    var_dump($this);
    var_dump($sender);
    var_dump($args);
  }

  public function run( $sender, $args ) {
    var_dump($this);
    var_dump($sender);
    var_dump($args);
  }

  public function daemonize(){
    if (php_sapi_name() == "cli") {
      while(true){
        pcntl_signal_dispatch();
        // dont write to console!
        //printf(ROOT.__CLASS__."\n");
        $this->crawlit();
        sleep(1);
        if(\Signal::get() == SIGHUP){
          \Signal::reset();
          break;
        }
      }
      printf("\n");
    }
  }

  private function crawlit() {
    try {
      $result = $this->d->fetchAssoc("SELECT * FROM crawl_queue LIMIT 10");
      foreach($result as $uri) {
        var_dump($uri['url']);
        $u = new Uri($uri['url']);
        $r = new robots($u);
        if (!$r->isBlocked($uri['url']) && $r->isTime()) {
          var_dump("Juhuuu");
          // we are ok to fetch!
          $head = $u->fetch();
          //var_dump($head);
        } else {
          var_dump("not time yet...");
        }
      }
    } catch (PDOException $e) {
      var_dump("Error");
      /*$sql = "CREATE TABLE crawl_queue (
        id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
        url VARCHAR(1024) NOT NULL,
        reg_date TIMESTAMP
        );";
        $this->d->exec($sql);*/
    }
  }
}
