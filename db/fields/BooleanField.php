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
  public $data_type = "integer";
  
  public function setup() {
    
  }


} 
