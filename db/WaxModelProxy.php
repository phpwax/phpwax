<?php

/**
 *
 * @package PhpWax
 **/

class WaxModelProxy {
  
  public $key = false;

  public function __construct($object) { 
    $this->key = WaxModelManager::set($object);
  }
  
  public function get() {
    return WaxModelManager::get($this->key);
  }
  
  
}