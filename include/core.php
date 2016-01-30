<?php

include_once('logger.php');
include_once('components.php');

class Core {

  private $components = array();
  public $site_root;

  private $time_start     =   0;
  private $time_end       =   0;
  private $time           =   0;

  private $log;
  private $comp;

  public function __construct() {
    $this->time_start= microtime(TRUE);
    $this->log = new Logger();
    $this->comp = new Component();
    // include all .php files in this folder.
    if (php_sapi_name() == "cli") {
      $this->site_root = dirname(dirname(__FILE__));
    }
    else {
      $this->site_root = $_SERVER['DOCUMENT_ROOT'];
    }
    $component_dirs = array(
			    $this->site_root . "/include",
			    $this->site_root . "/components",
			    );

    foreach($component_dirs as $dir) {
      $Directory = new RecursiveDirectoryIterator($dir);
      $Iterator = new RecursiveIteratorIterator($Directory);
      $Regex = new RegexIterator($Iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);
      foreach($Regex as $item) {
	include_once($item[0]);
      }
    }

    // Instantiate all classes that implements Components interface. But not $this.
    foreach(get_declared_classes() as $class) {
      if (in_array('Components', class_implements($class)) && $class != get_class($this)) {
	// Instantiate all classes that extends Component
	$this->components[$class] = new $class;
      }
    }
    $path = (isset($_GET['q']))?$_GET['q']:"html";
    dbg($path);
    $this->render($path);
  }

  public function __destruct(){
    $this->time_end = microtime(TRUE);
    $this->time = $this->time_end - $this->time_start;
    $this->log->add($this->time);
  }

  public function render($type='', $e='', $p='') {
    dbg($type);
    dbg($e);
    dbg($p);
    $page = new Template($this->site_root . "/templates/html.tpl");
    $html = array();
    $html = Events::trigger($type, 'render', 
			    ['page' => $html, 
			     'type' => $type]);
    $page->set("title", $html);
    $page->set("content", $html);
    $page->set("sidebar", $html);
    $page->set("messages", Messages::render());
    $this->add_css($page, "css/screen.css");
    echo $page->output();
  }

  public function add_css($template, $path, $media="screen") {
    $css = new Template($this->site_root . "/templates/link_css.tpl");
    $css->set("media", $media);
    $css->set("path", $path);
    $template->set("post_files", $css->output());
  }
}

/* Utility functions */

function dbg($var) {
  $data = var_export($var, TRUE);
  $backtrace = debug_backtrace();
  //var_dump($backtrace);
  $msg = "<div class=\"dbg_item\"><pre class=\"dbg_source\">" . $backtrace[0]['file'] . " - " . $backtrace[0]['line'] ."</pre>";
  $msg .= "<pre class=\"dbg_data\">" . $data . "</pre></div>";
  Messages::set($msg);
}