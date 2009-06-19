<?php

/**
 * EmailField class
 *
 * @package PHP-Wax
 **/
class DateTimeField extends WaxModelField {
  
  public $null = true;
  public $default = false;
  public $maxlength = false;
  public $widget = "DateInput";
  public $output_format = "Y-m-d H:i:s";
  public $save_format = "Y-m-d H:i:s";
  public $use_uk_date = false;

  public function validate() {
    $this->valid_length();
 	  $this->valid_required();
    $this->valid_format("datetime", '/^([0-9-]{4}-[0-9]{2}-[0-9]{2}\s{1}[0-9]{2}:[0-9]{2}:[0-9]{2}|[0-9-]{4}-[0-9]{2}-[0-9]{2})$/');
  }
  
  public function setup() {
    if($this->model->row[$this->field]==0 && $this->default=="now") {
      $this->model->row[$this->field] = date($this->save_format);
    }
  }
  
  public function output() {
    return date($this->output_format, strtotime($this->get()));
  }
  
  public function save() {
    $this->model->row[$this->field]= date($this->save_format, strtotime($this->get()));    
  }
  
  public function uk_date_switch() {
    
  }
  

} 
