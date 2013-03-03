<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Logger
 *
 * @author lars
 */
class Logger {

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
        $this->LOGFILENAME = $logfilename;
        $this->SEPARATOR = $separator;
        $this->HEADERS =
                'DATETIME' . $this->SEPARATOR .
                'ERRORLEVEL' . $this->SEPARATOR .
                'TAG' . $this->SEPARATOR .
                'VALUE' . $this->SEPARATOR .
                'LINE' . $this->SEPARATOR .
                'FILE';
        
        if(isset($_GET['loglevel'])){
            switch ($_GET['loglevel']) {
                case 'info' : $this->loglevel = $this->log_levels['info']; break;
                case 'warn' : $this->loglevel = $this->log_levels['warn']; break;
                case 'error' : $this->loglevel = $this->log_levels['error']; break;
                case 'debug' : $this->loglevel = $this->log_levels['debug']; break;
            }
        }
    }

    // Private method that will write the text logs into the $LOGFILENAME.
    private function log($errorlevel, $value = '', $tag) {

        $datetime = date("Y-m-d H:i:s");
        if (!file_exists($this->LOGFILENAME)) {
            $headers = $this->HEADERS . "\n";
        }

        $fd = fopen($this->LOGFILENAME, "a");

        if (@$headers) {
            fwrite($fd, $headers);
        }

        $debugBacktrace = debug_backtrace();
        $line = $debugBacktrace[1]['line'];
        $file = $debugBacktrace[1]['file'];

        $entry = array($datetime, $errorlevel, $tag, $value, $line, $file);

        fputcsv($fd, $entry, $this->SEPARATOR);

        fclose($fd);
    }

    private function getRequestedLoglevel() {
        if(isset($_GET['loglevel'])) {
            
        }
    }
    // Function to write not technical INFOrmation messages that will be written into $LOGFILENAME.
    function info($value = '', $tag = self::DEFAULT_TAG) {
        
        self::log($this->log_levels['info'], $value, $tag);
    }

    // Function to write WARNING messages that will be written into $LOGFILENAME.
    // These messages are non-fatal errors, so, the script will work properly even
    // if WARNING errors appears, but this is a thing that you must ponderate about.
    function warning($value = '', $tag = self::DEFAULT_TAG) {

        self::log($this->log_levels['warn'], $value, $tag);
    }

    // Function to write ERROR messages that will be written into $LOGFILENAME.
    // These messages are fatal errors. Your script will NOT work properly if an ERROR happens, right?
    function error($value = '', $tag = self::DEFAULT_TAG) {

        self::log($this->log_levels['error'], $value, $tag);
    }

    // Function to write DEBUG messages that will be written into $LOGFILENAME.
    // DEBUG messages are highly technical issues, like an SQL query or result of it.
    function debug($value = '', $tag = self::DEFAULT_TAG) {

        self::log($this->log_levels['debug'], $value, $tag);
    }

}

