<?php


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
    protected static $instance = null;
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
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
}