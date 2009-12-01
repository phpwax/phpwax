<?php

/**
 * EmailField class
 *
 * @package PHP-Wax
 **/
class EmailField extends CharField {
  
  public $maxlength = "100";
  public $data_type = "string";
  
  public function setup() {
    
  }

  public function validate() {
    $this->validations[]="email";
  }


} 
