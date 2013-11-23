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
    
    public function __construct() {
        //$this->log = new Logger(ROOT.'/../logs/app.log');
//        \Logger::info("wooot...");
//        $this->log->debug("Base is starting.");
    }
    
    public function __destruct() {
//        $this->log = new Logger(ROOT.'/../logs/app.log');
//        \Logger::info("Peak mem : ".(memory_get_peak_usage(TRUE)/1024)."kb");
    }
}

?>
