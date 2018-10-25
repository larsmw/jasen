<?php
/**
 * Linkhub
 *
 * This is a project....
 */


ini_set('display_errors', true);
ini_set('html_errors', false);
ini_set('log_errors', true);

function linkhub_exception_handler(\Exception $ex)
{
    echo "Linkhub fangede en fejl : " . $ex->getMessage();
    echo "<br />\nSe detaljer i loggen.";
    error_log(var_export($ex, true));
    mail("admin@linkhub.dk", "Exception", var_export($ex, true));
    die();
}

function linkhub_error_handler(int $errno, string $errstr, string $errfile, int $errline)
{
    echo "Linkhub fangede en fejl : " . $errstr;
    $err_str = $errno . " " . $errstr . " : @" . $errfile . "(".$errline.")";
    error_log($err_str);
    mail("admin@linkhub.dk", "Exception", $err_str);
    return false;
}

set_exception_handler('linkhub_exception_handler');
$old_error_handler = set_error_handler('linkhub_error_handler');

function auto_loader($className)
{
    error_log($className);
    $filename = ROOT."/core/classes/". str_replace("\\", '/', $className) . ".php";
    error_log($filename);
    if (file_exists($filename)) {
        include($filename);
        if (class_exists($className)) {
            return true;
        }
    }

    $errorMsg = "Autoloader failed with Class : $className, file : $filename";
    error_log($errorMsg);
    return false;
}

spl_autoload_register('App\auto_loader');

require_once(ROOT.'/core/classes/Interfaces.php');

class EventDispatcher
{
    private $map;
    private $obejcts;

    public function addListener($arg1, $arg2 = null)
    {
        if (!isset($this->objects[$arg1])) {
            $this->objects[$arg1] = new $arg1;
        }
        foreach (get_class_methods($arg1) as $method) {
            $this->map[$method][] = $arg1;
        }
    }
  
    public function invoke($eventName, $data = null)
    {
        if (isset($this->map[$eventName])) {
            foreach ($this->map[$eventName] as $callback) {
                call_user_func_array(array($this->objects[$callback], $eventName), array($data));
            }
        }
    }
}

class Core
{

    public function __construct()
    {

    // TODO: make an autoloader
    session_start();

        // Find plugins
        $files = scandir(ROOT."/core/classes");

    foreach($files as $file) {
      if(preg_match("/^.*\.(inc|php)$/i", $file)) {
          error_log("include file : ". $file);
        include_once(ROOT."/core/classes/".$file);
      }
    }
    $d = new EventDispatcher();
    foreach(get_declared_classes() as $c) {
      if(in_array('Plugin', class_implements($c))) {
        $d->addListener($c);
      }
    }

        $user = new User();
        $d->invoke("init");
        $d->invoke("run", $user);
        $d->invoke("destroy");
    }
}
