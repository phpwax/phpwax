<?php
/**
 * Register a log message along with a namespace
 *
 * Output can vary depending on environment.
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxLog {

  
  static public $log_file;

  static public $logs = array();
  static public $logs_enabled = array();
  static public $log_handler = array("self", "log");
  static public $auto_flush = true;
  
  static public function add($type, $message) {
    self::$log_file = ENV.".log";
    self::$logs[]=array($type=>$message);
    if(self::$auto_flush) call_user_func_array(self::$log_handler, self::output());
  }
  
  public function output() {
    $output = "";
    foreach(self::$logs as $type=>$log) {
      if(in_array( $type, self::$logs_enabled)) $output .= "[$type] $message"."\n";
    }
    self::flush();
    return $output;
  }
  
  public function flush() {
    self::$logs=array();
  }
  
  public function __set($name, $value) {
    self::$logs_enabled[$name]=$value;
  }
  
  public function log($output) {
    die($output);
    error_log($output, 3, self::$log_file);
  }
  

}

