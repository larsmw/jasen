<?php
/**
 * This is a search engine...
 *
 * @author Lars Nielsen <lars@lfweb.dk>
 */

namespace Linkhub;

use App\Crawler as Crawler;

define("ROOT", getcwd());
include ROOT."/core/core.php";

/**
 * Implementation of Application class
 */
class MyApp extends Application
{
    /**
     * Call constructor of parent.
     */
    public function __construct()
    {
        parent::__construct();

        // create some routes
        if (isset($_GET['q'])){
            $cmd = $_GET['q'];
        }
        else {
            $cmd = "";
        }
        $cmd = explode("/", $cmd);

        if ($cmd[0] === "crawl") {
            $crawler = Crawler::getInstance();
            $crawler->run();
            die();
        }
        if ($cmd === "addurl") {
            $f = new Url('add_url');
            $f->insert($f->get('url'));
        }
        if ($cmd[0] === "pager") {
            $p = new Pager();
            $p->run();
            die();
        }
        if ($cmd === "logout") {
            unset($_COOKIE['myusername']);
            session_start();
            session_unset();
            session_destroy();
            header("location:/");
        }
        $this->checkFormData();
        echo $this->parse();
    }

    /**
     * Renders html and adds to global template
     * @return html
     */
    private function parse()
    {
        // TODO: use twig
      $template = file_get_contents("templates/index.html");
      
      if (empty($_COOKIE['myusername'])) {
          $login_form = file_get_contents("templates/login-form.html");
      }
      else {
          $login_form = "Velkommen ".$_COOKIE['myusername']."!";
      }
      $template = str_replace("{%login-form}", $login_form, $template);
      
      return $template;
    }
}

try {
    $m = new MyApp();
}
catch (Exception $e) {
    error_log($e);
    echo "Fejlstatus : ERROR (se mere i log-filen)\n";
}
