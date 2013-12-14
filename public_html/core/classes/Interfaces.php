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
    protected static $_instance = null;
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
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}