<?php

require_once 'Interfaces.php';

require_once ROOT.'/../../conf.php';


class Database {

    public $db = null;

    //protected static $_instance = null;
    public function __construct()
    {
        global $databases;
        //var_dump($databases);
        //Thou shalt not construct that which is unconstructable!
        $this->db = new PDO("mysql:host=".$databases['default']['host'].";dbname=".$databases['default']['db']."", $databases['default']['user'], $databases['default']['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }
    protected function __clone()
    {
        //Me not like clones! Me smash clones!
    }

    /*    public static function getInstance()
    {
        global $databases;
//        var_dump($databases);
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$db = new PDO("mysql:host=".$databases['default']['host'].";dbname=".$databases['default']['db']."", $databases['default']['user'], $databases['default']['password']);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$_instance;
	}*/

    public function fetchAssoc($sql) {
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
}
