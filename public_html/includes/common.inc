<?php

if (php_sapi_name() == "cli") {
    // In cli-mode
  define('ROOT', getcwd()."/");
} else {
    // Not in cli-mode
  define('ROOT', $_SERVER['DOCUMENT_ROOT']."/");
}
require_once ROOT . 'vendor/autoload.php';

spl_autoload_register(function ($class) {
  $path = explode('\\', $class);
  $class = array_pop($path);
  include ROOT . 'modules/' . strtolower($class) . '/' . strtolower($class) . '.php';
});

require_once ROOT . 'includes/theme.inc';

// radagast32!

interface IPlugin
{
  function run( $sender, $args );
}

interface ICronPlugin
{
  function cron( $sender, $args );
}

class Controller {
  public $theme;
  public function __construct() {
    
    $this->theme = new ThemeEngine();
    //var_dump($this->theme);
    //$profile = new Twig_Profiler_Profile();
    //$this->twig->addExtension(new Twig_Extension_Profiler($profile));

    //$dumper = new Twig_Profiler_Dumper_Text();
    //echo $dumper->dump($profile);
    //var_dump($dumper);
  }

  public function __desctruct() {
  }
}

class App extends Controller {

  private $_plugins = array();  
  private $_crons = array();  
  protected $db;
  public $render;

  /**
   * Start here
   */
  public function __construct() {
    parent::__construct();
    
    $this->include_files();
    foreach ($this->getImplementingClasses("IPlugin") as $plugin ) {
      //var_dump($plugin);
      $this->_plugins[] = new $plugin;
    }
    foreach ($this->getImplementingClasses("ICronPlugin") as $plugin ) {
      //var_dump($plugin);
      $this->_crons[] = new $plugin;
    }

    //var_dump(get_defined_vars());
    
    /*if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      $if_modified_since = preg_replace('/;.*$/', '',   $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    } else {
      $if_modified_since = '';
    }
      
    $mtime = filemtime($_SERVER['SCRIPT_FILENAME']);
    $gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    
    //var_dump($if_modified_since);
    //var_dump($gmdate_mod);

    if ($if_modified_since == $gmdate_mod) {
      header("HTTP/1.0 304 Not Modified");
      exit;
    }
      
    header("Last-Modified: $gmdate_mod");*/

    // TODO: implement some caching
    /*    header('Content-type: text/html');


*/


    //header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (15)) . ' GMT');

    $seconds_to_cache = 0;
    $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
    header("Expires: $ts");
    header("Pragma: cache");
    header("Cache-Control: max-age=$seconds_to_cache");    
  }
  
  private function getImplementingClasses( $interfaceName ) {
    return array_filter(
        get_declared_classes(),
        function( $className ) use ( $interfaceName ) {
            return in_array( $interfaceName, class_implements( $className ) );
        }
    );
  }

  private function include_files() {
    if (null == ROOT) {
      define('ROOT', dirname(dirname(__FILE__)));
    }

    $dir    = ROOT.'modules';
    $dirs1 = array_diff(scandir($dir), array('..', '.'));

    foreach ($dirs1 as $dir) {
      $inc_file = ROOT.$dir."/".$dir.".php";
      if (file_exists($inc_file)) {
        include_once($inc_file);
      }
    }
  }
  
  public function run( )
  {
    foreach( $this->_plugins as $obs )
      $obs->run( $this, NULL);
    // http://stackoverflow.com/questions/7578526/twig-selecting-certain-blocks-and-rendering-them
    $this->theme->display('page.html',
      array_merge(array('title' => 'Twig rulez...'), $this->render));
  }
  
  public function cron( )
  {
    foreach( $this->_crons as $obs )
      $obs->cron( $this, NULL );
  }
  
  /**
   * Show some statistics and close down nicely
   */
  public function __destruct() {
    //echo "<div class=\"xdebug-report\">";
    //echo "Peak mem : ".(xdebug_peak_memory_usage()/1024)."kb";
    //echo "Running time : ".(xdebug_time_index())."</div>";
    //var_dump($this);
    //$this->theme->display('page.html', array('status' => 'The status is here...'));
  }
}


class StatusMonitor extends Controller implements IPlugin
{
  
  public function __construct() {
    parent::__construct();
  }
  
  function run( $sender, $args ) {
    //var_dump(get_class_methods($this->twig));
    $template = $sender->theme->loadTemplate('status.html');
    $sender->render['status'] = $template->renderBlock('content', array('status' => 'test'));
  }
}


class Database {
  public $db = null;
  //protected static $_instance = null;
  public function __construct()
  {
    global $databases;
    $databases['default'] = array(
      'host' => 'db1',
      'db' => 'linkhub',
      'user' => 'linkuser',
      'password' => 'radagast32!'
    );
    //var_dump($databases);
    $this->db = new PDO("mysql:host=".$databases['default']['host'].";dbname=".$databases['default']['db']."", $databases['default']['user'], $databases['default']['password']);
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  
  protected function __clone()
  {
    //Me not like clones! Me smash clones!
  }

  public function fetchAssoc($sql) {
    $stmt = $this->db->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  public function exec($sql) {
    try {
      $this->db->exec($sql);
    }
    catch(PDOException $e)
    {
      echo $sql . "<br>" . $e->getMessage();
    }
  }
  
  public function insertGetId($table, $value) {
    if(is_array($value))
      $value = $value[0];
    $sql = "SELECT id,name FROM $table WHERE name like '" . $value . "';";
    $r = $this->fetchAssoc($sql);
    if(count($r)===0) {
      $sql = "INSERT INTO $table (name) VALUES (:name)";
      $q = $this->db->prepare($sql);
      $q->execute(array(':name'=>$value));
      return $this->db->lastInsertId();
    }
    else {
      return $r[0]['id'];
    }
  }
}
