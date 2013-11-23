<?php

namespace interfaces;

interface IWebApplication
{
  function addRun( $observer );
  function addMenu( $menu );
}

interface IWebObject 
{
  function onRun( $sender, $args );
  function onMenu( &$menu );
}



class Singleton
{
    protected static $instances = array();
    protected function __construct()
    {
        //Thou shalt not construct that which is unconstructable!
    }
    protected function __clone()
    {
        //Me not like clones! Me smash clones!
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