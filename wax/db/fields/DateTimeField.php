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
  
  public function setup() {
    //default to current date if null
    if(!$this->model->{$this->field}){
      $this->model->{$this->field} = date("Y-m-d H:i:s");
    }
  }

  public function validate() {
    $this->valid_length();
 	  $this->valid_required();
    $this->valid_format("datetime", '/^[0-9-]{4}-[0-9]{2}-[0-9]{2}\s{1}[0-9]{2}:[0-9]{2}:[0-9]{2}$/');
  }

} 
