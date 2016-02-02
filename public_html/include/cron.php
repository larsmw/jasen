<?php

/**
 * This class manages maintenance tasks for the cron system.
 */
class Cron extends Component {

  private $num_log_entries;

  public function __construct() {
    $this->register('core', 'cron', array($this, "cron"));
    $this->num_log_entries = 500;
  }

  public function cron() {
    $db = new Database();
    $db->exec("DELETE FROM cron_log WHERE id NOT IN (SELECT id FROM ( select id from cron_log order by id desc limit " .$this->num_log_entries. " ) foo );");
  }
}
