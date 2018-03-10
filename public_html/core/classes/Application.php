<?php

namespace App;

function auto_loader($className) {
    $filename = ROOT."/core/classes/". str_replace("\\", '/', $className) . ".php";
    if (file_exists($filename)) {
        include($filename);
        if (class_exists($className)) {
            return TRUE;
        }
    }
    error_log($className);
    error_log($filename);
    return FALSE;
}

spl_autoload_register('App\auto_loader');

require_once(ROOT.'/core/classes/Interfaces.php');

ini_set('display_errors', 'FALSE');
ini_set('html_errors', 'FALSE');
ini_set('log_errors', 'TRUE');
//xdebug_enable();
//xdebug_start_code_coverage();


// TODO - define autoload

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-type: text/html; charset=utf-8');


/**
 * Description of Application
 *
 * @author lars
 */
class Application extends Base implements \interfaces\IWebApplication {

  private $_plugins = array();  

  protected $db;
  /**
   * Start here
   */
  public function __construct() {
    parent::__construct();

    foreach ($this->getImplementingClasses("interfaces\IWebObject") as $plugin ) {
        $this->_plugins[] = new $plugin;
    }

    $this->db = \Database::getInstance();
    
    $this->run();
    //$this->showDBStats();
  }
  
  private function getImplementingClasses( $interfaceName ) {
    return array_filter(
        get_declared_classes(),
        function( $className ) use ( $interfaceName ) {
            return in_array( $interfaceName, class_implements( $className ) );
        }
    );
  }
  
  public function addRun( $observer )
  {
    $this->_runObjects[] = $observer;
  }

  public function run( )
  {
    foreach( $this->_plugins as $obs )
      $obs->run( $this, "some parameter" );
  }


  /**
   * Show some statistics and close down nicely
   */
  public function __destruct() {
    echo "<div class=\"xdebug-report\">";
    echo "Peak mem : ".(xdebug_peak_memory_usage()/1024)."kb";
    echo "Running time : ".(xdebug_time_index())."</div>";
    //var_dump(parent);
    parent::__destruct();
  }
  
}

