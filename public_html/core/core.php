<?php

function __autoload($class_name) {
   require_once $class_name . '.php';
}

interface Plugin {
    function run();
}

class EventDispatcher {
    private $map;

    function addListener($arg1, $arg2 = null) {
      var_dump($arg1);
        if (is_a($arg1, 'Plugin')) {
            return $arg1->bindListeners($this);
        }
        $this->map[$arg1][] = $arg2;
    }

    function invoke($eventName, $data = null) {
        var_dump($this->map);
        foreach ($this->map[$eventName] as $callback) {
            call_user_func_array($callback, array($data));
        }
    }
}

class Core {

    public function __construct() {
//        var_dump(get_declared_interfaces());
        $d = new EventDispatcher();
        foreach(get_declared_classes() as $c) {
	  if(in_array('Plugin', class_implements($c))) {
            var_dump(get_class_methods($c));
	  }
        }
        $d->invoke("run");
    }
}

class MyPlugin implements Plugin {
    public function run() {
        echo "Running...<br >/";
    }
}

$c = new Core();

die("Core ended");