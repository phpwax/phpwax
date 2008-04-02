<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class CharField extends WaxModelField {
  
  public $maxlength = "255";
  
  public function setup() {
    
  }

  public function validate() {
    $this->valid_length();
 	  $this->valid_required();
  }


} 
