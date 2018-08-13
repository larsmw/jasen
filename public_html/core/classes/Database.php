<?php

require_once 'Interfaces.php';

define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'linkhub');

class Database extends interfaces\Singleton {

    public $db;

    public function __construct() {
      try {
        $this->db = new PDO(
            DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME,
            $_SERVER['APP_DATABASE_USER'], $_SERVER['APP_DATABASE_PASSWORD']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
          echo "Database error : " . $e->getMessage();
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
