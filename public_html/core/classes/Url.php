<?php

namespace App;

class Url extends App\Database {
    public function __construct($form_id) {
        parent::__construct();
//        var_dump($_POST);
    }

    public function get($name) {
        if(isset($_POST['url'])) {
            return $_POST['url'];
        }
        else
            {
                throw new UnknownException("Missing url");
            }
    }

    public function insert($url) {
        $sql = "INSERT INTO crawl_queue (url, added) VALUES (:url, NOW())";
        $q = $this->db->prepare($sql);
        $q->execute(array(':url' => $url));
    }
}

