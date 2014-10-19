<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Base
 *
 * @author lars
 */
class Base {

  protected $twig;

  public function __construct() {
    $loader = new Twig_Loader_Filesystem(ROOT.'/templates');
    $this->twig = new Twig_Environment($loader, array(
						      'debug' => true,
						      'cache' => ROOT.'/templates/compiled',
						      ));
  }
    
  public function __destruct() {
  }
}

?>
