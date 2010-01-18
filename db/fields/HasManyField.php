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
	public $join_order = false; //specify order of the returned joined objects
  public $data_type = "integer";
  
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
    if($this->model->row[$this->field] instanceof WaxModelCollection) return $this->model->row[$this->field];
    if($this->model->pk()) $constraints = array($this->join_field => $this->model->pk());
    return $this->model->row[$this->field] = new WaxModelCollection(get_class($this->model), $this->field, $this->target_model);
  }
  
  public function set($value) {
    if($value instanceof WaxRecordset)
      foreach($value as $val) $this->set($val);
    elseif($value instanceof WaxModel){
      $this->get()->add($value);
      $value->row[$this->join_field] = &$this->model;
    }
  }

  /**
   * this is used to defer writing of associations to the database adapter
   * i.e. this will only be run if this field's parent model is saved
   */
  public function save_assocations($model_pk, &$collection){
    $target = new $this->target_model;
    foreach($collection->rowset as $index => $row){
      $target->row = &$collection->rowset[$index];
      $target->{$this->join_field} = $model_pk;
      $target->save();
    }
    $collection->constraints = array($this->join_field => $model_pk);
  }

  public function unlink($value = false) {
    if(!$value) $value = $this->get(); //if nothing gets passed in to unlink then unlink everything
    if($value instanceof $this->target_model){
      if($this->model->row[$this->field] instanceof WaxModelAssociation) $this->model->row[$this->field]->remove($value);
      $value->{$this->join_field} = 0;
      $value->save();
    }
    if($value instanceof WaxRecordset) foreach($value as $row) $this->unlink($row);
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
    $collection = array_pop($args);
    $model = new $this->target_model();
    if($this->model->primval()) $model->filter($this->join_field, $this->model->primval());

    return call_user_func_array(array($model, $method), $args);
  }

} 
