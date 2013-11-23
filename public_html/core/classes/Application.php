<?php

namespace App;

require_once ROOT.'/core/classes/Base.php';
require_once ROOT.'/core/classes/User.php';
require_once ROOT.'/core/classes/Interfaces.php';
require_once ROOT.'/core/classes/Database.php';

/**
 * Description of Application
 *
 * @author lars
 */
class Application extends Base implements \interfaces\IWebApplication {

  private $_menus = array();
  private $_runObjects = array();  

  protected $db;
  /**
   * Start here
   */
  public function __construct() {
    parent::__construct();
    $this->db = \Database::getInstance();
    $this->addRun(new User());
    $this->onRun();
    //$this->showDBStats();
  }
  
  public function addRun( $observer )
  {
    $this->_runObjects[] = $observer;
  }

  public function addMenu( $menu )
  {
    $this->_menus[] = $menu;
  }

  public function onRun( )
  {
    foreach( $this->_runObjects as $obs )
      $obs->onRun( $this, "some parameter" );
  }


  /**
   * private test method
   * @private
   */
  private function buildMenu($parent, $menu) {
    // Menu builder function, parentId 0 is the root
    //      echo "<pre>";var_dump($menu);echo "</pre>";
    $html = "";
    if (isset($menu['parents'][$parent]))
      {
	$html .= "<ul class=\"nav\">\n";
	foreach ($menu['parents'][$parent] as $itemId)
	  {
	    if(!isset($menu['parents'][$itemId]))
	      {
		$html .= "<li class=\"dropdown\">\n  <a href='#'>".$menu['items'][$itemId]['label']."</a>\n</li> \n";
	      }
	    if(isset($menu['parents'][$itemId]))
	      {
		$html .= "<li class=\"dropdown\">\n  <a href='".$menu['items'][$itemId]['link']."' class=\"dropdown-toggle\">".$menu['items'][$itemId]['label']."</a> \n";
		$html .= $this->buildMenu($itemId, $menu);
		$html .= "</li> \n";
	      }
	  }
	$html .= "</ul> \n";
      }
    return $html;
  }


  public function showMenu($id) {

      $sql = "SELECT id, label, link, parent FROM menu_item ORDER BY weight, parent, label";

      $result = $this->db->fetchAssoc($sql);

      // Create a multidimensional array to conatin a list of items and parents
      $menu = array(
		  'items' => array(),
		  'parents' => array()
      );

      // Builds the array lists with data from the menu table
      foreach($result as $items)
          {
              // Creates entry into items array with current menu item id ie. $menu['items'][1]
              $menu['items'][$items['id']] = $items;
              // Creates entry into parents array. Parents array contains a list of all items with children
              $menu['parents'][$items['parent']][] = $items['id'];
          }
      
      return "<nav id=\"main-menu\">".$this->buildMenu(0, $menu)."</nav>";
  }

  /**
   * Show some statistics and close down nicely
   */
  public function __destruct() {
    echo "Peak mem : ".(memory_get_peak_usage(TRUE)/1024)."kb";
    //var_dump(parent);
    parent::__destruct();
  }
  
  private function showDBStats() {
      $r = $this->db->fetchAssoc("SHOW STATUS;");
      //var_dump($r);
      foreach($r as $v) {
          echo $v['Variable_name']." = '".$v['Value']."'<br />";
      }
  }
}

?>
