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
  
  static public function init() {
    if(self::$log_file) return true;
    self::$log_file = LOG_DIR.ENV.".log";
    ini_set("error_log",self::$log_file);
  }
  
  static public function log($type, $output, $file=false) {
    self::init();
    if($file) ini_set("error_log",LOG_DIR.$file.".log");
    if(Config::get("log_".$type)) error_log("[$type] $output");
    if($file) ini_set("error_log",self::$log_file);
  }
  
  static public function unparameterise($string, $params){
    if(!$params) return $string;
    return str_replace(array_fill(0, count($params), "?"), $params, $string);
  }
}

