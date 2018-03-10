<?php

namespace App;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once ROOT.'/core/classes/Logger.php';

/**
 * Description of Base
 *
 * @author lars
 */
class Base {   
    //put your code here
//    public static $log;

    protected $db;
    
    public function __construct() {
        // Load basic settings from settings file.
        $this->db = new \Database($this->registry->db_settings);

        $this->routes = new Router();
    }
    
    public function __destruct() {
//        $this->log = new Logger(ROOT.'/../logs/app.log');
//        \Logger::info("Peak mem : ".(memory_get_peak_usage(TRUE)/1024)."kb");
    }
}


