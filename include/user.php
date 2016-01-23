<?php

class User extends Component {
  public $log;

  public function __construct() {
    $this->log = new Logger();
    $this->register('core', 'render', array($this, "render"));
    $this->register('core', 'cron', array($this, "cron"));
  }

  public function render($r, $e, $p) {
    $db = new Database();
    $t = new Template("templates/user.tpl");
    $t->set("user_name", "Lars Nielsen");
    $t->set("css_class", 'user');
    return array('content' => $t->output(),
		 'title' => "Lars Nielsen",
				 );
  }

  public function cron() {
    //$this->log->add("");
  }
}

