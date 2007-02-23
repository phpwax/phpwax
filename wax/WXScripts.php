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
    $gen = new WXGenerator("controller", array_slice($argv, 1));
  }
  
}

?>