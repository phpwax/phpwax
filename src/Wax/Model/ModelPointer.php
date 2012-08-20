<?php
namespace Wax\Model;

/**
 * Very simple class that holds a link to a potential model.
 * Used primarily for lazy loading, but when operated on, loads and returns the real model it points to.
 *
 * @package phpwax
 */
class ModelPointer {
  
  public $target_model    = FALSE;
  public $key             = FALSE;
  public $instance        = FALSE;
  
  
  public function __construct($model, $key) {
    $this->target_model = $model;
    $this->key = $key;
  }
  
  public function _init() {
    if($this->instance) $instance = $this->instance;
    else {
      $instance = new $this->target_model($this->key);
      $this->instance = $instance;
    }
  }
  
  public function __call($method, $args) {
    $this->_init();
    return call_user_func_array(array($this->instance, $method), $args);    
  }
  
  public function __get($value) {
    $this->_init();
    return $this->instance->$value;
  }
  
  public function __set($name, $value) {
    $this->_init();
    return $this->instance->$value = $name;
  }

}