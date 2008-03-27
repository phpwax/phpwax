<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class CharField extends WaxModelField {
  
  public $null = false;
  public $default = false;
  public $maxlength = "255";
  
  public function setup() {
    
  }

  public function validate() {
    $this->valid_length();
  }


} 
