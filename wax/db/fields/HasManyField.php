<?php

/**
 * HasManyField class
 *
 * @package PHP-Wax
 **/
class HasManyField extends WaxModelField {
  
  public $maxlength = "11";
  public $model_name = false;
  public $join_field = false;
  public $editable = false;
  
  
  public function setup() {
    $this->col_name = false;
    $class_name = get_class($this->model);
    if(!$this->model_name) $this->model_name = Inflections::camelize($this->field, true);
    if(!$this->join_field) $this->join_field = Inflections::underscore($class_name)."_".$this->model->primary_key;
  }

  public function validate() {
    return true;
  }
  
  public function get() {
    $model = new $this->model_name();
    return new WaxModelAssociation($model->filter(array($this->join_field=>$this->model->primval) ) , $this->model, $this->field);
  }
  
  public function set($value) {
    if($value instanceof $this->model_name){
      $value->{$this->join_field} = $this->model->primval();
      $value->save();
    }
    if($value instanceof WaxRecordset) {
      foreach($value as $row){
        $row->{$this->join_field} = $this->model->primval();
        $row->save();
      }
    }
  }
  
  public function unlink($value) {
    if($value instanceof $this->model_name){
      $value->{$this->join_field} = 0;
      $value->save();
    }
    if($value instanceof WaxRecordset) {
      foreach($value as $row){
        $row->{$this->join_field} = 0;
        $row->save();
      }
    }
  }
  
  public function save() {
    return true;
  }

  public function before_sync() {
    if($this->model_name != get_class($this->model)){
      //define a foreign key in the target model and recursively sync that model
      $output .= WaxModel::model_setup($this->model_name, $this->join_field, "ForeignKey", array("col_name" => $this->join_field, "table" => $this->model->table));
      $output .= parent::before_sync();
      return $output;
    }
  }
  
  public function __call($method, $args) {
    $model = new $this->model_name();
    $model->filter(array($this->join_field=>$this->model->primval));

    return call_user_func_array(array($model, $method), $args);
  }

} 
