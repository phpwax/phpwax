<?php

/**
 * FloatField class
 *
 * @package PHP-Wax
 **/
class FloatField extends CharField {
  
  public $maxlength = "4,2";
  public $data_type = "float";

  public function setup_validations() {
    parent::setup_validations();
    if($this->required || !$this->blank) $this->validations[]="float";
    
  }
} 
