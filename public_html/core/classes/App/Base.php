<?php
namespace Linkhub\App;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once ROOT.'/core/classes/Logger.php';

/**
 * Description of Base
 *
 * @author lars
 */
class Base
{

    protected $db;
    protected $router;

    public function __construct()
    {
        // Load basic settings from settings file.
        $this->db = new \Database([]);
        $this->router = new \Router();
    }
    
    public function __destruct()
    {
    }

    protected function schema($sql, $version, $module)
    {
        // execute sql from callie
        // update system table with module and version.
    }

    private function systemSchema()
    {
        $sql = <<<EOL
CREATE TABLE IF NOT EXISTS system (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(255),
  version INT
);
EOL;
        $this->db->exec($sql);
    }
}

/*
 * Utility functions
 */

function is_cli()
{
    if (php_sapi_name() == "cli") {
        //In cli-mode
        return true;
    } else {
        //Not in cli-mode
        return false;
    }
}
