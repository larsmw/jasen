<?php

class Logger {
  public function add($message) {
    $dbt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
    $caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : null;  
    $db = new Database();
    $sql = "INSERT INTO cron_log (source, message) " .
      "VALUES (:source, :message)";

    
    $params = array('source' => 
		    $dbt[1]['class'].$dbt[1]['type'].$dbt[1]['function']."()",
		    'message' => $message);
    $db->execute($sql, $params);
  }
}