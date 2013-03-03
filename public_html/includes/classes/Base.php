<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Base
 *
 * @author lars
 */
class Base {   
    //put your code here
    public static $log;
    
    public function __construct() {
        $this->log = new Logger(ROOT.'/../logs/app.log');
        $this->log->info("Base is starting.");
    }
    
    public function __destruct() {
//        $this->log = new Logger(ROOT.'/../logs/app.log');
        $this->log->info("Peak mem : ".(memory_get_peak_usage(TRUE)/1024)."kb");
    }
}

?>
