<?php

/**
 * FloatField class
 *
 * @package PHP-Wax
 **/
class FloatField extends WaxModelField {
  
  public $maxlength = "4,2";
  
  public function setup() {
    
  }

  public function validate() {
    $this->valid_float();
 	  $this->valid_required();
  }


} 
