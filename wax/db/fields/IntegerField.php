<?php

/**
 * IntegerField class
 *
 * @package PHP-Wax
 **/
class IntegerField extends WaxModelField {
  
  public $maxlength = "11";
  
  public function setup() {
    
  }

  public function validate() {
    $this->valid_length();
 	  $this->valid_required();
    $this->valid_format("number", "/^[0-9]*$/");
  }


} 
