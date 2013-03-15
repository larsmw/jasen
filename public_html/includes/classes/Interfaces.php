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
