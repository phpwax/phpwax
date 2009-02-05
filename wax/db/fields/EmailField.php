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
    if(!$this->blank) $this->valid_format("email", '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i');
  }


} 
