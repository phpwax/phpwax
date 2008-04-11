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

  
  static $log_file=false;
  static $log_handler = array("WaxLog", "log");
  
  static public function add($type, $message) {
    if(!self::$log_file) self::$log_file = LOG_DIR.ENV.".log";
    ini_set("error_log",self::$log_file);
    call_user_func_array(self::$log_handler, array($type, $message));
  }
  
  static public function log($type, $output) {
    if(defined("DEBUG_".strtoupper($type))) {
      error_log("[$type] $output");
    }
  }
  

}

