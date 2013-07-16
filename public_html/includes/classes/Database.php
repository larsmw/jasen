<?php

require_once 'Interfaces.php';

define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'lms');
define('DB_USER', 'root');
define('DB_PASS', 'password');


class Database extends Singleton {
    
    public function __construct() {
        
        $db = new PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
}

$o = Database::getInstance();


