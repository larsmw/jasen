<?php

require_once 'Interfaces.php';

define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'se_links');
define('DB_USER', 'se');
define('DB_PASS', '1234');


class Database extends \Singleton {

    public $db;

    public function __construct() {
        
        $this->db = new PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function fetchAssoc($sql) {
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
}


