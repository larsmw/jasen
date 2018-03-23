<?php

namespace App;

ini_set('display_errors', FALSE);
ini_set('html_errors', FALSE);
ini_set('log_errors', TRUE);

function linkhub_exception_handler( \Exception $ex ) {
  echo "Linkhub fangede en fejl : " . $ex->getMessage();
  echo "<br />\nSe detaljer i loggen.";
  error_log(var_export($ex, true));
  mail("admin@linkhub.dk", "Exception", var_export($ex, true));
  die();
  }

function linkhub_error_handler( int $errno, string $errstr, string $errfile, int $errline) {
  echo "Linkhub fangede en fejl : " . $errstr;
  $err_str = $errno . " " . $errstr . " : @" . $errfile . "(".$errline.")";
  error_log($err_str);
  mail("admin@linkhub.dk", "Exception", $err_str);
  return false;
}

set_exception_handler('App\linkhub_exception_handler');
$old_error_handler = set_error_handler('App\linkhub_error_handler');

function auto_loader($className) {
  $filename = ROOT."/core/classes/". str_replace("\\", '/', $className) . ".php";
  if (file_exists($filename)) {
    include($filename);
    if (class_exists($className)) {
      return TRUE;
    }
  }

  $errorMsg = "Autoloader failed with Class : $className, file : $filename";
  error_log($errorMsg);
  return FALSE;
}

spl_autoload_register('App\auto_loader');

require_once(ROOT.'/core/classes/Interfaces.php');

ini_set('display_errors', 'TRUE');
ini_set('html_errors', 'TRUE');
ini_set('log_errors', 'TRUE');


header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Tue, 13 Jul 1976 16:10:00 GMT"); // Date in the past
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
    session_start();

    foreach ($this->getImplementingClasses("interfaces\IWebObject") as $plugin ) {
        $this->_plugins[] = new $plugin;
    }

    $this->db = \Database::getInstance();
    
    $this->run();
    $this->router->exec();
  }

  /**
   * @return An array of objects that is implementing an interface
   */
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
    echo "Peak mem : ".(\xdebug_peak_memory_usage()/1024)."kb";
    echo "Running time : ".(\xdebug_time_index())."</div>";
    //var_dump(parent);
    parent::__destruct();
  }
  
}

