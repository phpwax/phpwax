<?php

/**
 * TextField class
 *
 * @package PHP-Wax
 **/
class TextField extends WaxModelField {
  
  public $maxlength = false;
	public $widget = "TextareaInput";
  
  public function validate() {
    $this->valid_required();
  }


} 
