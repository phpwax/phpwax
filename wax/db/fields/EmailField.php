<?php

/**
 * EmailField class
 *
 * @package PHP-Wax
 **/
class EmailField extends WaxModelField {
  
  public $maxlength = "100";
  
  public function setup() {
    
  }

  public function validate() {
    $this->valid_length();
 	  $this->valid_required();
    $this->valid_format("email", '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i');
  }


} 
