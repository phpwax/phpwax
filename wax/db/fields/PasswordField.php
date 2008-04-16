<?php

/**
 * PasswordField class
 *
 * @package PHP-Wax
 **/
class PasswordField extends CharField {
  
  public $maxlength = "32";
  public $unique = false;
  
  public function setup() {
    
  }

  public function validate() {
 	  $this->valid_required();
  }
  
  
  
}