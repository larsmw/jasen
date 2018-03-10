<?php

function auto_loader($className) {
    $filename = ROOT."/core/classes/". str_replace("\\", '/', $className) . ".php";
    if (file_exists($filename)) {
        include($filename);
        if (class_exists($className)) {
            return TRUE;
        }
    }
    return FALSE;
}

spl_autoload_register('auto_loader');

namespace App;

require_once(ROOT.'/core/classes/Interfaces.php');

/**
 * Description of Application
 *
 * @author lars
 */
class Application extends Base implements \interfaces\IWebApplication {

  private $_plugins = array();  

  protected $db;
  /**
   * Start here
   */
  public function __construct() {
    parent::__construct();

    foreach ($this->getImplementingClasses("interfaces\IWebObject") as $plugin ) {
        $this->_plugins[] = new $plugin;
    }

    $this->db = \Database::getInstance();
    
    $this->run();
    //$this->showDBStats();
  }
  
  private function getImplementingClasses( $interfaceName ) {
    return array_filter(
        get_declared_classes(),
        function( $className ) use ( $interfaceName ) {
            return in_array( $interfaceName, class_implements( $className ) );
        }
    );
  }
  
  public function addRun( $observer )
  {
    $this->_runObjects[] = $observer;
  }

  public function run( )
  {
    foreach( $this->_plugins as $obs )
      $obs->run( $this, "some parameter" );
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
    echo "<div class=\"xdebug-report\">";
    echo "Peak mem : ".(xdebug_peak_memory_usage()/1024)."kb";
    echo "Running time : ".(xdebug_time_index())."</div>";
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
