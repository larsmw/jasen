<?php

use PHPUnit\Framework\TestCase;

define('ROOT', getcwd()."/public_html");

include(ROOT."/core/classes/Application.php");

final class CoreTest extends TestCase {

    function setUp() {
        echo getcwd();
    }
    
    public function testCore() {
        echo getcwd();
        $a = new App\Application();

        $this->assertTrue(is_object($a), "a is not an object");
    }

}
