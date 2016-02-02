<?php

class User extends Component {
  public $log;

  public function __construct() {
    $this->log = new Logger();
    // register paths to handle
    $this->register('user', 'render', array($this, "render"));
    $this->register('user/*', 'render', array($this, "render"));
    $this->register('core', 'cron', array($this, "cron"));
  }

  public function render($r, $e, $p) {
    dbg($r);
    dbg($e);
    dbg($p);
    switch($p['type']) {
    case 'user' : $name = "logged in username...";
      break;
    default: $name = "Log ind...";
    }

    $db = new Database();
    $t = new Template("templates/user.tpl");
    $t->set("user_name", $name);
    $t->set("css_class", 'user');
    return array('content' => $t->output(),
		 'title' => "Lars Nielsen",
		 );
  }

  public function cron() {
    //$this->log->add("");
  }
}

