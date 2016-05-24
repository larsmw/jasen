<?php

class Database {

  private $dbh;
  private $conf;

  public function __construct() {
    $this->conf = $this->get_config();
    try {
      $this->dbh = new PDO($this->conf['pdo'], $this->conf['user'], $this->conf['pass']);
      $q = "show tables;";
      $r = $this->q($q);

      return TRUE;
    } catch(PDOException $e){
      if ($e->getCode() == 1049) {
	// DB doesn't exists. Create a new.
	try {
	  $dbh = new PDO($this->conf['pdo_nodb'], $this->conf['user'], $this->conf['pass']);
	  $dbh->exec("CREATE DATABASE `" . $this->conf['db'] . "`;");
	  $this->dbh = new PDO($this->conf['pdo'], $this->conf['user'], $this->conf['pass']);
	  return TRUE;
	} catch(PDOException $e){
	  die("DB Error");
	}
      }
      return false;
    }
  }

  public function get_config() {
    $db = "linkhub";
    return array("db" => $db,
		 "pdo" => "mysql:host=localhost;dbname=$db;charset=utf8",
		 "pdo_nodb" => "mysql:host=localhost;charset=utf8",
		 "user" => "root",
		 "pass" => "root");
  }

  public function q($sql) {
    $r = array();
    if (!isset($this->dbh)) {
      $this->conf = $this->get_config();
      //var_dump($this->conf);
      $this->dbh = new PDO($this->conf['pdo'], $this->conf['user'], $this->conf['pass']);
    }
    $q = $this->dbh->prepare($sql);
    $q->execute();
    if ($q->rowCount() > 0) {
      foreach($this->dbh->query($sql) as $row) {
	$r[] = $row;
      }
    }
    return $r;
  }

  public function exec($sql) {
    try {
      $result = $this->dbh->exec($sql);
      return TRUE;
    } catch(PDOException $e){
      syslog(LOG_INFO, $this->dbh->errorInfo());
      die("DB Error");
    }
  }

  public function insert($sql) {
    try {
      $result = $this->dbh->exec($sql);
      $id = $this->dbh->lastInsertId();
      return $id;
    } catch(PDOException $e){
      syslog(LOG_INFO, $this->dbh->errorInfo());
      die("DB Error");
    }
  }

  public function execute($sql, $params) {
    try {
      $prep = $this->dbh->prepare($sql);
      $result = $prep->execute($params);
      return TRUE;
    } catch(PDOException $e){
      syslog(LOG_INFO, $this->dbh->errorInfo());
      die("DB Error");
    }
  }

  public function quote($str) {
    return $this->dbh->quote($str);
  }

  public function tableExists($tablename) {
    $r = $this->q("SELECT table_name FROM information_schema.tables WHERE " .
		  "table_schema = '".$this->conf['db']."' AND table_name = '".$tablename."';");
    return !empty($r);
  }
}
