<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class WaxModelField {
  
  public $null = false;
  public $default = false;
  public $maxlength = false;
  public $minlength = false;
  public $choices = false;
  public $blank = true;
  public $label = false;
  public $help_text = false;
  public $format = false;
  public $validations = array();
  public $errors = array();
  
  

  public function __construct($field_type, $options = array()) {
    
  }
  
  public function validate($model) {
    
  }
  
  public function form_field() {
    
  }
  
  


} // END class 

