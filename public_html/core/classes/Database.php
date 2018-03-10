<?php

require_once 'Interfaces.php';

define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'linkhub');
define('DB_USER', 'root');
define('DB_PASS', 'was&87Bki');


class Database extends interfaces\Singleton {

    public $db;

    public function __construct() {

        try {        
        $this->db = new PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOExceptio $e) {
          echo $e->getMessage();
          die();
        }
    }
    
    public function fetchAssoc($sql) {
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }        
}


