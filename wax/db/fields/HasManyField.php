<?php

/**
 * HasManyField class
 *
 * @package PHP-Wax
 **/
class HasManyField extends WaxModelField {
  
  public $maxlength = "11";
  public $target_model = false;
  public $join_field = false;
  public $editable = false;
  
  
  public function setup() {
    $this->col_name = false;
    $class_name = get_class($this->model);
    if(!$this->target_model) $this->target_model = Inflections::camelize($this->field, true);
    if(!$this->join_field) $this->join_field = Inflections::underscore($class_name)."_".$this->model->primary_key;
  }

  public function validate() {
    return true;
  }
  
  public function get() {
    if(! $this->model->row[$this->field] instanceof WaxModelAsociation) {
      $this->model->row[$this->field]=$this->get_links();
    } else return $this->model->{$this->field};
  }
  
  public function get_links() {
    $model = new $this->target_model();
    $model->filter(array($this->join_field=>$this->model->primval));
    foreach($model->rows() as $row) {
      $ids[]=$row[$model->primary_key];
    }
    return new WaxModelAssociation($this->model,$model, $ids);
  }
  
  public function set($value) {
    if($value instanceof $this->target_model){
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
    if($value instanceof $this->target_model){
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
    if($this->target_model != get_class($this->model)){
      //define a foreign key in the target model and sync that model
      $target_model = get_class($this->model);
   	  $link = new $this->target_model;
   	  $link->define($this->join_field, "ForeignKey", array("col_name" => $this->join_field, "target_model" => $target_model));
   	  return $link->syncdb();
    }
  }
  
  public function __call($method, $args) {
    $model = new $this->target_model();
    $model->filter(array($this->join_field=>$this->model->primval));

    return call_user_func_array(array($model, $method), $args);
  }

} 
