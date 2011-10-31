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
  
  public function __call($method, $args) {
    $model = $this->get();
    return call_user_func_array(array($model, $method), $args);
  }
  
  
}