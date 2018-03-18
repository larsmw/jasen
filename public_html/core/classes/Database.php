<?php

require_once 'Interfaces.php';
require_once(ROOT."/../../linkhub.settings.php");


class Database {
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', PHP_DB_DATABASE);
define('DB_USER', PHP_DB_USER);
define('DB_PASS', PHP_DB_PASS);


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

    /**
     * @return assoc array of results
     */
    public function fetchAssoc($sql) {
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }        
}
