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
  public $loaded = false;
  
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
    return $this->model->row[$this->field] = new WaxModelCollection($this->model, $this->field, new $this->target_model);
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
  public function save_assocations(){
    print_r($this->get()); exit;
    foreach($this->get() as $target){
      $target->{$this->join_field} = $this->model->pk();
      $target->save();
    }
  }

  public function unlink($value = false) {
      //rewrite this function
  }
  
  public function save() {}

  public function before_sync() {
      //rewrite this function
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
  
  protected function create_association($target = false, $rowset = array()){
     return new WaxModelCollection($this->model, $this->field,$this->target_model,$rowset);
   }
  
  public function __call($method, $args) {
    return call_user_func_array(array($this->get(), $method), $args);
    $model = new $this->target_model();
    if($this->model->pk()) $model->filter($this->join_field, $this->model->pk());
    return call_user_func_array(array($model, $method), $args);
  }

} 
