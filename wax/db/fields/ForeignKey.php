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
  public $table;
  public $col_name = false;
  public $model_name;
  
  
  public function setup() {
    if(!$this->table) $this->table = $this->field;
    if(!$this->col_name) $this->col_name = $this->table."_id";
    if(!$this->model_name) $this->model_name = WaxInflections::camelize($this->table);
  }

  public function validate() {

  }
  
  public function get() {
    $model = new $this->model_name($this->model->{$this->col_name})
  }
  
  public function set() {
    
  }


} 
