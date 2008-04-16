<?php

/**
 * EmailField class
 *
 * @package PHP-Wax
 **/
class DateTimeField extends WaxModelField {
  
  public $null = false;
  public $default = false;
  public $maxlength = false;
  public $widget = "DateInput";

  public function validate() {
    $this->valid_length();
 	  $this->valid_required();
    $this->valid_format("datetime", '/^[0-9-]{4}-[0-9]{2}-[0-9]{2}\s{1}[0-9]{2}:[0-9]{2}:[0-9]{2}$/');
  }

} 
