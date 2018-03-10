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

    protected $db;
    
    public function __construct() {
        // Load basic settings from settings file.
        $this->db = new \Database([]);

    }
    
    public function __destruct() {
    }
}


