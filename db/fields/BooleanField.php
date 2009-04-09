<?php

/**
 * BooleanField class
 *
 * @package PHP-Wax
 **/
class BooleanField extends WaxModelField {
  
  public $null = false;
  public $default = 0;
  public $maxlength = false;
  public $choices = array(0 => "No", 1 => "Yes");
  public $widget = "SelectInput";
  
  public function setup() {
    
  }

  public function validate() {
    $this->valid_length();
 	  $this->valid_required();
    $this->valid_format("number", "/^[0-1]?$/");
  }

} 