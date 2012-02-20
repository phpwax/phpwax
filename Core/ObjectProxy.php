<?php
namespace Wax\Core;

class ObjectProxy {

  public $key = false;

    public function __construct($object) {
      $this->key = ObjectManager::set($object);
    }
  
    public function get() {
      return ObjectManager::get($this->key);
    }
  
    public function __call($method, $args) {
      $model = $this->get();
      return call_user_func_array(array($model, $method), $args);
    }

}