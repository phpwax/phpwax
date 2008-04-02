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
  public $join_field = false;
  
  
  public function setup() {
    $this->col_name = false;
    $class_name = get_class($this->model);
    if(!$this->join_field) $this->join_field = Inflections::underscore($class_name)."_id";
  }

  public function validate() {
    return true;
  }
  
  public function get() {
    $class = Inflections::camelize($this->model_name);
    $model = new $class();
    print_r($model->filter(array($this->join_field=>$this->model->row[$this->model->primary_key]))->all());
    exit;
  }
  
  public function set($value) {
    return true;
  }
  
  public function save() {
    return true;
  }


} 
