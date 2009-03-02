<?php

/**
 * EmailField class
 *
 * @package PHP-Wax
 **/
class EmailField extends CharField {
  
  public $maxlength = "100";
  
  public function setup() {
    
  }

  public function validate() {
    parent::validate();
    if(!$this->blank) $this->valid_format("email", '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-\+]+)*@[a-zA-Z0-9-]+(\.[a-zA-z0-9-]+)*(\.[a-zA-Z]{2,4})$/i');
  }


} 
