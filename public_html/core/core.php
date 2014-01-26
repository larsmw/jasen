<?php

function __autoload($class_name) {
   require_once $class_name . '.php';
}

interface BindableListener {
    function bindListeners(EventDispatcher $dispatcher);
}

interface Plugin {
    function run();
}

class EventDispatcher {
    private $map;
    function addListener($arg1, $arg2 = null) {
        if (is_a($arg1, 'BindableListener')) {
            return $arg1->bindListeners($this);
        }
        $this->map[$arg1][] = $arg2;
    }
    function dispatchEvent($eventName, $data = null) {
        foreach ($this->map[$eventName] as $callback) {
            call_user_func_array($callback, array($data));
        }
    }
}

class Core {

    public function __construct() {
//        var_dump(get_declared_interfaces());
        foreach(get_declared_classes() as $c) {
            var_dump(class_implements($c));
//            var_dump(get_class_methods($c));
        }
        $d = new EventDispatcher();
        $d->dispatchEvent("run");
    }
}




class MyPlugin implements Plugin {
    public function run() {
        echo "Running...<br >/";
    }
}



$c = new Core();





die("Core ended");
