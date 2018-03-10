<?php

require_once 'Interfaces.php';

define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'linkhub');
define('DB_USER', $_SERVER['PHP_DB_USER']);
define('DB_PASS', $_SERVER['PHP_DB_PASS']);


class Database extends interfaces\Singleton {

    public $db;

    public function __construct() {

        try {        
        $this->db = new PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOExceptio $e) {
          echo "database error";
          die();
        }
    }
    
    public function fetchAssoc($sql) {
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }        
}


