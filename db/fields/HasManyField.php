<?php

/**
 * HasManyField class
 *
 * @package PHP-Wax
 **/
class HasManyField extends WaxModelField {
  
  public $target_model = false;
  public $join_field = false;
  public $editable = false;
  public $is_association = true;
  public $eager_loading = false;
  public $widget = "MultipleSelectInput";
  
  
  public function setup() {
    $this->col_name = false;
    $class_name = get_class($this->model);
    if(!$this->target_model) $this->target_model = Inflections::camelize($this->field, true);
    if(!$this->join_field) $this->join_field = Inflections::underscore($class_name)."_".$this->model->primary_key;
  }

  public function validate() {
    return true;
  }
  
  public function get($filters = false) {
    $target = new $this->target_model;
    if($filters) $target->filter($filters);
    if($this->eager_loading) return $this->eager_load($target);
    return $this->lazy_load($target);
  }
  
  public function eager_load($target) {
		$cache = WaxModel::get_cache($this->target_model.":".md5(serialize($target->filters)), $this->field, $this->model->primval, false);
		if(is_array($cache)) return new WaxModelAssociation($this->model, $target, $cache, $this->field);
    $vals = $target->filter(array($this->join_field=>$this->model->primval))->all();
		WaxModel::set_cache($this->target_model.":".md5(serialize($target->filters)), $this->field, $this->model->primval, $vals->rowset);
		return new WaxModelAssociation($this->model, $target, $vals->rowset, $this->field);
  }
  
  public function lazy_load($target) {
    $target->filter(array($this->join_field=>$this->model->primval));
    foreach($target->rows() as $row) {
      $ids[]=$row[$target->primary_key];
    }
    return new WaxModelAssociation($this->model, $target, $ids, $this->field);
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
   	  $link->syncdb();
    }
  }
  
  public function create($attributes) {
    $model = new $this->target_model();
    $new_model = $model->create($attributes);
    $new_model->{$this->join_field} = $this->model->primval;
    return $new_model;
  }
  
  public function get_choices() {
    $j = new $this->target_model;
    if($this->identifier) $j->identifier = $this->identifier;
    foreach($j->all() as $row) $this->choices[$row->{$row->primary_key}]=$row->{$row->identifier};
    return $this->choices;
  }
  
  public function __call($method, $args) {
    $model = new $this->target_model();
    $model->filter(array($this->join_field=>$this->model->primval));

    return call_user_func_array(array($model, $method), $args);
  }

} 
