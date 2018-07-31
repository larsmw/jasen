<?php

namespace Linkhub;

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Tue, 13 Jul 1976 16:10:00 GMT"); // Date in the past
header('Content-type: text/html; charset=utf-8');


/**
 * Description of Application
 *
 * @author lars
 */
class Application extends \App\Base implements \interfaces\IWebApplication
{

    private $_plugins = array();

    protected $db;
    /**
     * Start here
     */
    public function __construct()
    {
        if (is_cli()) {
            ini_set('html_errors', 'FALSE');
        }

        parent::__construct();
        if (session_status() == PHP_SESSION_NONE && !is_cli()) {
            session_start();
        }

        foreach ($this->getImplementingClasses("interfaces\IWebObject") as $plugin) {
            $this->_plugins[] = new $plugin;
        }

        $this->db = \Database::getInstance();
    
        $this->run();
        $this->router->exec();
    }

    /**
     * @return An array of objects that is implementing an interface
     */
    private function getImplementingClasses($interfaceName)
    {
        return array_filter(
            get_declared_classes(),
            function ($className) use ($interfaceName) {
                return in_array($interfaceName, class_implements($className));
            }
        );
    }
  
    public function addRun($observer)
    {
        $this->_runObjects[] = $observer;
    }

    public function run()
    {
        foreach ($this->_plugins as $obs) {
            $obs->run($this, "some parameter");
        }
    }

    /**
     * Show some statistics and close down nicely
     */
    public function __destruct()
    {
        if (!is_cli()) {
            echo "<div class=\"xdebug-report\">";
            echo "Peak mem : ".(\xdebug_peak_memory_usage()/1024)."kb";
            echo "Running time : ".(\xdebug_time_index())."</div>";
        }
        parent::__destruct();
    }
}
