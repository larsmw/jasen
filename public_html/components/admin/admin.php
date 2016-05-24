<?php
/**
 * {@inheritdoc}
 */

/**
 * {@inheritdoc}
 */
class Admin extends Component {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->d = new Database();
    $this->register('html', 'render', array($this, "render"));
    if (isset($_POST['url'])) {
      require_once("components/indexer/classifier.php");
      $crawler = new Crawler();
      var_dump($_POST['url']);
      $resp = $crawler->fetch($_POST['url']);
      $classify = new Classifier(new PersistentMemory());
      $classify->train($_POST['categories'], $resp['content']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($r, $e, $p) {

    $t = new Template("templates/admin.tpl");
    $o = $this->html_form("add_terms", array(
					     1 => "Politik",
					     2 => "Api",
					     3 => "wiki",
					     4 => "webshop",
					     ));
    $t->set("content", $o);

    return array('content' => $t->output());
  }

  public function html_form($id, $elements) {
    $o  = "";
    $o .= "<form id=\"".$id."\" method=\"post\">";
    $o .= $this->html_form_option($elements, "categories");
    $o .= "<input type=\"text\" name=\"url\" size=\"40\" />";
    $o .= "<input type=\"submit\" value=\"Train\" />";
    $o .= "</form>";
    return $o;
  }

  public function html_form_option($elements, $name) {
    $o  = "<select name=\"$name\">";
    foreach($elements as $k=>$v) {
      $o .= "<option value=\"".$k."\">" . $v . "</option>";
    }
    $o .= "</select>";
    return $o;
  }
}