<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once ROOT.'/core/classes/Interfaces.php';

/**
 * Description of Logger
 *
 * @author lars
 */
class Logger implements interfaces\IWebObject {

    // Name of the file where the message logs will be appended.
    private $LOGFILENAME;
    // Define the separator for the fields. Default is comma (,).
    private $SEPARATOR;
    // headers
    private $HEADERS;

    // Default tag.

    const DEFAULT_TAG = '--';

    private $log_levels = array(
            'info' => 1,
            'warn' => 2,
            'error' => 4, 
            'debug' => 8,
            );
    private $loglevel;

    public function __construct($logfilename = '../logs/app.log', $separator = ',') {

    }
    
    public function run( $sender, $args ) {
        
    }

    // Private method that will write the text logs into the $LOGFILENAME.
    private function log($errorlevel, $value = '', $tag) {
        echo $errorlevel." : ".$value." : ".$tag."<br />\n";
    }

    // Function to write not technical INFOrmation messages that will be written into $LOGFILENAME.
    public static function info($value = '', $tag = self::DEFAULT_TAG) {
        self::log("WARNING", $value, $tag);
    }

    // Function to write WARNING messages that will be written into $LOGFILENAME.
    // These messages are non-fatal errors, so, the script will work properly even
    // if WARNING errors appears, but this is a thing that you must ponderate about.
    public static function warning($value = '', $tag = self::DEFAULT_TAG) {
    }

    // Function to write ERROR messages that will be written into $LOGFILENAME.
    // These messages are fatal errors. Your script will NOT work properly if an ERROR happens, right?
    public static function error($value = '', $tag = self::DEFAULT_TAG) {
    }

    // Function to write DEBUG messages that will be written into $LOGFILENAME.
    // DEBUG messages are highly technical issues, like an SQL query or result of it.
    public static function debug($value = '', $tag = self::DEFAULT_TAG) {
    }

}

