<?php

/**
 * PrimaryKey class
 *
 * @package PHP-Wax
 **/
class ForeignKey extends WaxModelField {
  
  public $null = false;
  public $default = false;
  public $maxlength = "11";
  public $model_name = false;
  
  
  public function setup() {
    if(!$this->table) $this->table = $this->field;
    $this->col_name = $this->table."_id";
    if(!$this->model_name) $this->model_name = Inflections::camelize($this->table);
  }

  public function validate() {

  }
  
  public function get() {
    $class= $this->model_name;
    $model = new $class($this->model->{$this->col_name});
    return $model;
  }
  
  public function set(WaxModel $value) {
    $this->model->{$this->col_name} = $value->{$value->primary_key};
    unset($this->model->{$this->field});
  }
  
  public function save() {
    
  }


} 
