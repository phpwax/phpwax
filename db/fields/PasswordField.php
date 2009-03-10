<?php

/**
 * PasswordField class
 *
 * @package PHP-Wax
 **/
class PasswordField extends CharField {
  
  public $maxlength = "32";
  public $widget = "PasswordInput";
  
  public function setup() {
    
  }

  public function validate() {
 	  $this->valid_required();
  }
  
  
  
}