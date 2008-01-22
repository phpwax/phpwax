<?php

/**
 * Filter Chain Class and interface
 * Stores a chain of filters that can be implemented by any class
 *
 *
 * @package PhpWax
 **/
 

/**
 * Interface for all classes that wish to hook into the WaxFilter Chain
 *
 * @package PhpWax
 **/
 
interface WaxFilterInterface {
  
  public function filter($value);
  
}
 
class WaxFilter {
  
  static public $filters = array();
  static public $remove_filters = array();
  public $input = "";
 
  /**
   *
   * @return void
   * @author /bin/bash: niutil: command not found
   **/
  public function __construct($input) {
    $this->input = $input;
  }

  /**
   * run_pre_filters function
   *
   * @return $output
   **/
  public function run_filters($trigger) {
    foreach(self::$remove_filters as $remove_filter) {
      self::$filters[$remove_filter["trigger"]][$remove_filter["class"]][$remove_filter["method"]]=false;
    }
    
    foreach(self::$filters[$trigger] as $class) {
      foreach($class as $method=>$args) {
        if(is_array($method)) $this->input = call_user_func_array(array($class, $method), $args);
      }
    }
    return $this->input;
  }
  
  
  /**
   * add_filter function
   *
   * @return void
   **/
  static public function add_filter($trigger, $class, $method, $args=array()) {
    self::$filters[$trigger][$class][$method]=$args;
  }
  
  /**
   * remove_filter function
   *
   * @return void
   **/
  static public function remove_filter($trigger, $class, $method) {
    self::$remove_filters[]=array("trigger"=>$trigger, "class"=>$class, "method"=>$method);
  }
  
  

} 