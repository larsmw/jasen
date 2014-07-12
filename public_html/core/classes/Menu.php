<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This handles creation of menus and provides means to create menu
 * items.
 *
 * @author lars
 */
class Menu implements interfaces\IWebObject {
    //put your code here
    public function run( $sender, $args ) {
        var_dump($sender); 
        var_dump($this);
    }
}
