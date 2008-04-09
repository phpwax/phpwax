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

  
  static public $log_file=false;

  static public $logs = array();
  static public $logs_enabled = array();
  static public $log_handler = array("WaxLog", "write");
  static public $auto_flush = true;
  
  static public function add($type, $message) {
    if(!self::$log_file) self::$log_file = LOG_DIR.ENV.".log";
    ini_set("error_log",self::$log_file);
    if(in_array( $type, self::$logs_enabled)) self::$logs[]=array($type, $message);
    if(self::$auto_flush) call_user_func(self::$log_handler, self::output());
  }
  
  static public function log($type) {
    self::$logs_enabled[$type]=true;
  }
  
  public function output() {
    $output = array();
    foreach(self::$logs as $log) {
      $output[]= "[".$log[0]."] ". $log[1];
    }
    self::flush();
    return $output;
  }
  
  public function flush() {
    self::$logs=array();
  }
 
  
  public function write($output) {
    foreach($output as $log) error_log($log);
  }
  

}

