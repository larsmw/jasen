<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once ROOT.'/includes/classes/Base.php';

/**
 * Description of Application
 *
 * @author lars
 */
class Application extends Base {

    /**
     * Start here
     */
    public function __construct() {
        parent::__construct();
        //    parent::$log.info("Hej");
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
	  $html .= "<ul>\n";
	  foreach ($menu['parents'][$parent] as $itemId)
	    {
	      if(!isset($menu['parents'][$itemId]))
		{
		  $html .= "<li>\n  <a href='".$menu['items'][$itemId]['link']."'>".$menu['items'][$itemId]['label']."</a>\n</li> \n";
		}
	      if(isset($menu['parents'][$itemId]))
		{
		  $html .= "<li>\n  <a href='".$menu['items'][$itemId]['link']."'>".$menu['items'][$itemId]['label']."</a> \n";
		  $html .= $this->buildMenu($itemId, $menu);
		  $html .= "</li> \n";
		}
	    }
	  $html .= "</ul> \n";
	}
      return $html;
    }
/*echo buildMenu(0, $menu);
        echo "False";
	}*/
    public function showMenu($id) {
      $con = mysql_connect("localhost", "linkhub", "was&87Bki");
      mysql_select_db("linkhub");
      // Select all entries from the menu table
      $result=mysql_query("SELECT id, label, link, parent FROM menu_item ORDER BY parent, label");
      // Create a multidimensional array to conatin a list of items and parents
      $menu = array(
		    'items' => array(),
		    'parents' => array()
		    );
      // Builds the array lists with data from the menu table
      while ($items = mysql_fetch_assoc($result))
	{
	  // Creates entry into items array with current menu item id ie. $menu['items'][1]
	  $menu['items'][$items['id']] = $items;
	  // Creates entry into parents array. Parents array contains a list of all items with children
	  $menu['parents'][$items['parent']][] = $items['id'];
	}

      echo "<nav>".$this->buildMenu(0, $menu)."</nav>";
    }
    
    public function __destruct() {
        //parent::$log->info("Peak mem : ".(memory_get_peak_usage(TRUE)/1024)."kb");
        var_dump(parent);
        parent::__destruct();
    }
}

?>
