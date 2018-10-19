<?php

namespace interfaces;

interface IWebApplication
{
  function addRun( $observer );
}

interface IWebObject
{
  // define a path and a callback, so we know how to call your object
  function route();
  // A default run method will be called on every object
  function run( $sender, $args );
}



class Singleton
{
    protected static $instances = array();
    protected function __construct()
    {
        //Prevent construction of Singleton objects
    }
    protected function __clone()
    {
        // Prevent cloning Singletons.
    }

    public static function getInstance()
    {
        $cls = get_called_class();
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static;
        }
        return self::$instances[$cls];
    }
}
