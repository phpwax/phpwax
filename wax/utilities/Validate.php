<?php
/**
 *  
 * @package PHP-Wax
 * @author Ross Riley
 **/
class Validate
{
  static public $validations = array();
  static public $remove_validations = array();
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
  public function validate($trigger) {
    foreach(self::$remove_validations as $remove_validation) {
      self::$validations[$remove_validation["trigger"]][$remove_validation["class"]][$remove_validation["method"]]=false;
    }
    
    foreach(self::$validations[$trigger] as $class) {
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