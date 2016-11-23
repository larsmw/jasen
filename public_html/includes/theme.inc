<?php

// Handle integration with Twig.


/**
 * Handles basic Twig 
 */
class ThemeEngine extends Twig_Environment {

  public function __construct(Twig_LoaderInterface $loader = null, array $options = array()) {
    $loader = new Twig_Loader_Filesystem(ROOT . 'templates');
    parent::__construct($loader, array(
        'cache' => '/tmp',
        'debug' => true,
    ));
  }

}