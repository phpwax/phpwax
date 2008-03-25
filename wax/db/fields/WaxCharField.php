<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class WaxCharField extends WaxModelField {
  
  public $null = false;
  public $default = false;
  public $maxlength = "255";  
  
  public function char_field() {
    
  }
  
  public function text_field() {
    
  }
  
  public function integer_field() {
    if(!$this->maxlength) $this->maxlength = 11;
    
  }
  
  public function date_field() {
    
  }
  
  public function datetime_field() {
    
  }

  public function email_field() {
    
  }

  public function validate($model) {
    
  }


} // END class 

