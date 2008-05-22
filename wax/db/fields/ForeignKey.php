<?php

/**
 * ForeignKey class
 *
 * @package PHP-Wax
 **/
class ForeignKey extends WaxModelField {
  
  public $maxlength = "11";
  public $model_name = false;
  public $widget = "SelectInput";
  public $choices = array();
  public $identifier = false;
  
  
  public function setup() {
    if(!$this->table) $this->table = $this->field;
    if(!$this->model_name) $this->model_name = Inflections::camelize($this->table);
    $link = new $this->model_name;
    if($this->identifier) {
      $this->choices[""]="Select";
      foreach($link->all() as $row) $this->choices[$row->{$row->primary_key}]=$row->{$this->identifier};
    }
    // Overrides naming of field to model_id if col_name is not explicitly set
    if($this->col_name == $this->field) $this->col_name = Inflections::underscore($this->model_name)."_".$link->primary_key;
  }

  public function validate() {
    return true;
  }
  
  public function get() {
    $class= $this->model_name;
    $model = new $class($this->model->{$this->col_name});
    return $model;
  }
  
  public function set($value) {
    if($value instanceof WaxModel) {
      $this->model->{$this->col_name} = $value->{$value->primary_key};
      unset($this->model->{$this->field});
      return $this->model->save();
    }
  }
  
  public function save() {
    return true;
  }
  
  public function __get($name) {
    if($name == "value") return $this->model->{$this->col_name};
    return parent::__get($name);
  }


} 
