<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);

/**
Routing ideas

  /MODULE/ID/METHOD

  eg. 
  /page/23/view
  /search/the+string+to+find/list
  /admin/user/
  /admin/user/add
  /crawler/control/start
**/


$pid = pcntl_fork(); // fork
    if ($pid < 0)
        exit;
    else if ($pid) // parent
        exit;
    else { // child
   
        $sid = posix_setsid();
       
        if ($sid < 0)
            exit;
           
        for($i = 0; $i <= 60; $i++) { // do something for 5 minutes
            sleep(5);
	    echo "$i...\n";
        }
    }
