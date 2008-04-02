<?php

/**
 * HasManyField class
 *
 * @package PHP-Wax
 **/
class HasManyField extends WaxModelField {
  
  public $null = false;
  public $default = false;
  public $maxlength = "11";
  public $model_name = false;
  
  
  public function setup() {
    $this->col_name = false;
  }

  public function validate() {
    return true;
  }
  
  public function get() {
    $class = Inflections::camelize($this->model_name);
    $model = new $class();
    return $model->filter(array("id"=>$this->model->row[$this->model->primary_key]))->all();
  }
  
  public function set($value) {
    return true;
  }
  
  public function save() {
    return true;
  }


} 
