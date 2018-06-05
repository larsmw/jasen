<?php

class Url extends App\Base {

  public function __construct($url) {
    parent::__construct();
  }

  public function get($name) {
    
  }

  public function insert($url) {
    $sql = "INSERT INTO crawl_queue (url, added) VALUES (:url, NOW())";
    $q = $this->db->prepare($sql);
    $q->execute(array(':url' => $url));
  }
}

