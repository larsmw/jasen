<?php

use PHPUnit\Framework\TestCase;

define('ROOT', getcwd());

include("core/classes/Application.php");

final class CoreTest extends TestCase {
    public function __construct() {
        echo getcwd();
    }
    
    public function testCore() {
        $a = new \Linkhub\myApp();

        $this->assertTrue(is_object($a), "a is not an object");
    }

}
