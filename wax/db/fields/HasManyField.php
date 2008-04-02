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
    if(!$this->table) $this->table = $this->field;
    if(!$this->col_name) $this->col_name = $this->table."_id";
    if(!$this->model_name) $this->model_name = Inflections::camelize($this->table);
  }

  public function validate() {

  }
  
  public function get() {
    $class = $this->model_name;
    $model = new $class();
    return $model->filter(array("id"=>$this->model->id))->all();
  }
  
  public function set($value) {
    return true;
  }
  
  public function save() {
    
  }


} 
