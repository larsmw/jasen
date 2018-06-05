<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Linkhub;

final class CoreTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $conf = ['db_user'=>'root','db_pass'=>'HxnkQVzR'];
        $_SERVER['PHP_VALUE'] = serialize($conf);
    }
    
    public function testCore()
    {
        $a = new \Linkhub\Application();
        $this->assertTrue(is_object($a), "a is not an object");
    }
}
