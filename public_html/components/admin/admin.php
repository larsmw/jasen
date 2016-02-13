<?php

class Admin extends Component {

  public function __construct() {
    $this->d = new Database();
    $this->register('html', 'render', array($this, "render"));
  }

  public function render($r, $e, $p) {

    $t = new Template("templates/admin.tpl");
    $o = $this->html_form("add_terms", array(
					     1 => "Politik",
					     2 => "Computer",
					     ));
    $t->set("content", $o);

    return array('content' => $t->output());
  }

  public function html_form($id, $elements) {
    $o  = "";
    $o .= "<form id=\"".$id."\">";
    $o .= "</form>";
    return $o;
  }

  public function html_form_option($elements) {
    $o  = "<option>";
    foreach($elements as $k=>$v) {
      $o .= "<option value=\"".$k."\">" . $k . "</option>";
    }
    $o .= "</option>";
    return $o;
  }
}