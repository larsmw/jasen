<?php

require_once 'Interfaces.php';

define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'linkhub');

class Database extends interfaces\Singleton {

    public $db;

    public function __construct() {
      try {
        var_dump(getenv('APP_DATABASE_USER'));
          die();
          $conf = unserialize($_SERVER['PHP_VALUE']);
        $this->db = new PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, $conf['db_user'], $conf['db_pass']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
          error_log($e->getMessage());
          throw $e;
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
