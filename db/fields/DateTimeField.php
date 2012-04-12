<?php

/**
 * EmailField class
 *
 * @package PHP-Wax
 **/
class DateTimeField extends WaxModelField {

  public $null = true;
  public $default = false;
  public $database_default = "NULL";
  public $maxlength = false;
  public $widget = "DateInput";
  public $output_format = "Y-m-d H:i:s";
  public $input_format = false;
  public $save_format = "Y-m-d H:i:s";
  public $use_uk_date = false;
  public $data_type = "date_and_time";

  public function setup() {
    if(!$this->input_format) $this->input_format = $this->output_format;
    if($this->model->row[$this->field]==0 && is_string($this->default)) {
      $this->model->row[$this->field] = date($this->save_format,strtotime($this->default));
    }
    if($this->required) $this->validations["datetime"];
  }

  public function output() {
    return $this->strtotime_reformat($this->get(), $this->output_format);
  }

  public function validate() {
    $this->model->row[$this->field] = $this->strtotime_reformat($this->get(), $this->save_format);
  }

  public function uk_date_switch() {

  }

  public function get(){
    return $this->strtotime_reformat(parent::get(), $this->input_format);
  }

  private function strtotime_reformat($val, $format){
    if(($ret = strtotime(str_replace("/", "-", $val))) !== false) return date($format, $ret);
  }
}
