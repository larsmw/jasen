<?php

if (php_sapi_name() != "cli") {
  die("call me from cli...");
}

include_once('include/core.php');
include_once('include/events.php');
include_once('include/database.php');

$c = new Core();
$d = new Database();

$r = $d->q("SELECT id FROM cron_log limit 1;");
if (empty($r)) {
  $sql = "CREATE TABLE cron_log ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, source VARCHAR(1024) NOT NULL, message TEXT, last_run TIMESTAMP);";
  $d->exec($sql);
}

Events::trigger('core', 'cron', array());
