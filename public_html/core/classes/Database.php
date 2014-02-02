<?php

require_once 'Interfaces.php';

//require_once ROOT.'/Config.php';


class Database {

    public static $db = null;

    protected static $_instance = null;
    public function __construct()
    {
        //Thou shalt not construct that which is unconstructable!
        $this->db = new PDO('mysql:host=localhost;dbname=se_links', 'root', 'was87Bki');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }
    protected function __clone()
    {
        //Me not like clones! Me smash clones!
    }

    public static function getInstance()
    {
        global $databases;
//        var_dump($databases);
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$db = new PDO('mysql:host=localhost;dbname=se_links', 'root', 'was87Bki');
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$_instance;
    }

    public function fetchAssoc($sql) {
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
}
