<?php

require_once 'Interfaces.php';

define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'linkhub');

class Database extends interfaces\Singleton {

    public $db;

    public function __construct() {
      try {
          $conf = unserialize($_SERVER['PHP_VALUE']);
          var_dump($conf);
          die();
        $this->db = new PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
          echo "database error : " . $e->getMessage();
          die();
        }
    }

    /**
     * @return assoc array of results
     */
    public function fetchAssoc($sql) {
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }        
}
