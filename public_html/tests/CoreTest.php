<?php

use PHPUnit\Framework\TestCase;

define('ROOT', getcwd());

include("public_html/core/classes/Application.php");

final class CoreTest extends TestCase {

    function setUp() {
        echo getcwd();
    }
    
    public function testCore() {
        echo getcwd();
        $a = new \Linkhub\myApp();

        $this->assertTrue(is_object($a), "a is not an object");
    }

}
