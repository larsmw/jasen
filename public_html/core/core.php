<?php

/*function __autoload($class_name) {
   require_once $class_name . '.php';
   }*/

include_once("classes/Interfaces.php");

require_once("Config.php");

class EventDispatcher {
    private $map,$obejcts;

    function addListener($arg1, $arg2 = null) {
            if(!isset($this->objects[$arg1])) {
                $this->objects[$arg1] = new $arg1;
            }
            foreach(get_class_methods($arg1) as $method) {
                $this->map[$method][] = $arg1;
            }
    }

    function invoke($eventName, $data = null) {
        if(isset($this->map[$eventName])) {
            foreach ($this->map[$eventName] as $callback) {
                call_user_func_array(array($this->objects[$callback], $eventName), array($data));
            }
        }
    }
}

class Core {

    public function __construct() {
        // Find plugins
        $files = scandir(ROOT."/core/classes");
//        var_dump($files);
        foreach($files as $file) {
            if(preg_match("/^.*\.(inc|php)$/i", $file)) {
                include_once(ROOT."/core/classes/".$file);
            }
        }
        $d = new EventDispatcher();
        foreach(get_declared_classes() as $c) {
            if(in_array('Plugin', class_implements($c))) {
                $d->addListener($c);
            }
        }
        $d->invoke("init");
        $d->invoke("run");
        $d->invoke("destroy");
    }
}

/*class App implements Plugin {
    private $config;

    public function init() {
        $this->config = new Config();
    }

    public function run() {
//        var_dump($this->config);
        echo "App starting...";
    }
    }*/

class MyPlugin implements Plugin {
    public function run() {
        echo "Running...<br />";
    }
}



//echo xdebug_memory_usage()." bytes memory<br />";
//echo xdebug_time_index()." seconds";
