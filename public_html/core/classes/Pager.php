<?php

class Pager extends App\Base {

    protected $db;

    public function __construct() {
        /*
          Place code to connect to your DB here.
        */
        $this->db = Database::getInstance();
        // include your code to connect to DB.

    }

    public function run() {
        if(isset($_GET['q'])){
            $cmd = $_GET['q'];
        }
        else {
            $cmd = "";
        }
        $cmd = explode("/", $cmd);


        echo '<html>
  <head>
    <title>List of links.</title>
    <link rel="stylesheet" type="text/css" href="/css/menu.css" />
    <link rel="stylesheet" type="text/css" href="/css/default.css" />
  </head>
  <body>';


        $tbl_name="urls";		//your table name
        // How many adjacent pages should be shown on each side?
        $adjacents = 5;
//        var_dump($_GET['q']);        
        /* 
           First get total number of rows in data table. 
           If you have a WHERE clause in your query, make sure you mirror it here.
        */
        $query = "SELECT COUNT(*) as num FROM $tbl_name";
        $total_pages = $this->db->fetchAssoc($query);
        $total_pages = $total_pages[0]['num'];
        
        /* Setup vars for query. */
        $targetpage = "/pager"; 	//your file name  (the name of this file)
        $limit = 10; 								//how many items to show per page
        $page = $cmd[1];
        if($page) 
            $start = ($page - 1) * $limit; 			//first item to display on this page
        else
            $start = 0;								//if no page var is given, set start to 0
        
        /* Get data. */
        $sql = "SELECT p.name as http,d.name as host,u.url as url FROM urls u inner join domain d on u.domain_id=d.id inner join protocols p on p.id=u.scheme_id LIMIT $start, $limit";
        $result = $this->db->fetchAssoc($sql);
//        var_dump($result);        
        /* Setup page vars for display. */
        if ($page == 0) $page = 1;					//if no page var is given, default to 1.
        $prev = $page - 1;							//previous page is page - 1
        $next = $page + 1;							//next page is page + 1
        $lastpage = ceil($total_pages/$limit);		//lastpage is = total pages / items per page, rounded up.
        $lpm1 = $lastpage - 1;						//last page minus 1
        
        /* 
           Now we apply our rules and draw the pagination object. 
           We're actually saving the code to a variable in case we want to draw it more than once.
        */
        $pagination = "";
        if($lastpage > 1)
            {	
                $pagination .= "<div class=\"pagination\">";
                //previous button
                if ($page > 1) 
                    $pagination.= "<a href=\"$targetpage/$prev\">< previous</a>";
                else
                    $pagination.= "<span class=\"disabled\">< previous</span>";	
                
                //pages	
                if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
                    {	
                        for ($counter = 1; $counter <= $lastpage; $counter++)
                            {
                                if ($counter == $page)
                                    $pagination.= "<span class=\"current\">$counter</span>";
                                else
                                    $pagination.= "<a href=\"$targetpage/$counter\">$counter</a>";					
                            }
                    }
                elseif($lastpage > 5 + ($adjacents * 2))	//enough pages to hide some
                    {
                        //close to beginning; only hide later pages
                        if($page < 1 + ($adjacents * 2))		
                            {
                                for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
                                    {
                                        if ($counter == $page)
                                            $pagination.= "<span class=\"current\">$counter</span>";
                                        else
                                            $pagination.= "<a href=\"$targetpage/$counter\">$counter</a>";					
                                    }
                                $pagination.= "...";
                                $pagination.= "<a href=\"$targetpage/$lpm1\">$lpm1</a>";
                                $pagination.= "<a href=\"$targetpage/$lastpage\">$lastpage</a>";		
                            }
                        //in middle; hide some front and some back
                        elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
                            {
                                $pagination.= "<a href=\"$targetpage/1\">1</a>";
                                $pagination.= "<a href=\"$targetpage/2\">2</a>";
                                $pagination.= "...";
                                for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
                                    {
                                        if ($counter == $page)
                                            $pagination.= "<span class=\"current\">$counter</span>";
                                        else
                                            $pagination.= "<a href=\"$targetpage/$counter\">$counter</a>";					
                                    }
                                $pagination.= "...";
                                $pagination.= "<a href=\"$targetpage/$lpm1\">$lpm1</a>";
                                $pagination.= "<a href=\"$targetpage/$lastpage\">$lastpage</a>";		
                            }
                        //close to end; only hide early pages
                        else
                            {
                                $pagination.= "<a href=\"$targetpage/1\">1</a>";
                                $pagination.= "<a href=\"$targetpage/2\">2</a>";
                                $pagination.= "...";
                                for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
                                    {
                                        if ($counter == $page)
                                            $pagination.= "<span class=\"current\">$counter</span>";
                                        else
                                            $pagination.= "<a href=\"$targetpage/$counter\">$counter</a>";					
                                    }
                            }
                    }
                
                //next button
                if ($page < $counter - 1) 
                    $pagination.= "<a href=\"$targetpage/$next\">next �</a>";
                else
                    $pagination.= "<span class=\"disabled\">next �</span>";
                $pagination.= "</div>\n";		
            }

/*		while($row = mysql_fetch_array($result))
            {
                
                // Your while loop here
                
                }*/
        foreach($result as $row) {
            $url = $row['http']."://".$row['host'].$row['url'];
            $contenttype = "";//get_headers($url,1)['Content-Type'];
            echo "<a href=\"".$url."\" target=\"_blank\">".$url."</a> [".$contenttype."]<br /><br />";
        }
        echo $pagination;
    }
}
