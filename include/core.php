<?php

include_once('logger.php');

class Core {

  private $components = array();
  public $site_root;

  private $time_start     =   0;
  private $time_end       =   0;
  private $time           =   0;

  private $log;

  public function __construct() {
    $this->time_start= microtime(TRUE);
    $this->log = new Logger();

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
  }

  public function __destruct(){
    $this->time_end = microtime(TRUE);
    $this->time = $this->time_end - $this->time_start;
    $this->log->add($this->time);
    //$dir = $_SERVER['DOCUMENT_ROOT'];
    //$free = disk_free_space($dir);
    //$free_to_mbs = $free / (1024*1024*1024);
    //echo 'You have '.sprintf("%01.2f",$free_to_mbs).' GBs free';
  }

  public function render($r=NULL) {
    $page = new Template($this->site_root . "/templates/html.tpl");
    $html = array();
    $html = Events::trigger('core', 'render', ['page' => $html, 'type' => 'html']);
    $page->set("title", $html['title']);
    $page->set("content", $html['content']);
    $page->set("sidebar", $html['sidebar']);
    $this->add_css($page, "css/screen.css");
    echo $page->output();
  }

  public function add_css($template, $path, $media="screen") {
    $out = "<link rel=\"stylesheet\" media=\"$media\" type=\"text/css\" href=\"$path\" />";
    $template->set("post_files", $out);
  }
}

