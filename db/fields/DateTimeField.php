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
  public $data_type = "date_and_time";

  public function setup() {
    if($this->model->row[$this->field]==0 && is_string($this->default)) {
      $this->model->row[$this->field] = date($this->save_format,strtotime($this->default));
    }
    if($this->required) $this->validations["datetime"];
  }

  public function output() {
    return date($this->output_format, strtotime($this->get()));
  }

  public function validate() {
    if($value = $this->get()) $this->model->row[$this->field]= date($this->save_format, strtotime($value));
  }

  public function uk_date_switch() {

  }

  public function get(){
    return date($this->output_format, strtotime(parent::get()));
  }
}
