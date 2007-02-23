<?php

class WXScripts {
  
  public function __construct($type, $argv) {
    ob_end_clean();
    $this->$type($argv);
  }
  
  public function app_setup() {
    require_once dirname(__FILE__).'/../app/config/environment.php';
  }
  
  public function controller($argv) {
    $this->app_setup();
    $gen = new WXGenerator("controller", array_slice($argv, 1));
  }
  
  public function email($argv) {
    $this->app_setup();
    $gen = new WXGenerator("email", array_slice($argv, 1));
  }
  
  public function test() {
    $this->app_setup();
    $gen = new WXGenerator("test", array_slice($argv, 1));
  }
  
}

?>