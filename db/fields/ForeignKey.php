<?php

/**
 * ForeignKey class
 *
 * @package PHP-Wax
 **/
class ForeignKey extends WaxModelField {
  
  public $maxlength = "11";
  public $target_model = false;
  public $widget = "SelectInput";
  public $choices = array();
  public $is_association = true;
  public $data_type = "integer";
  
  public function setup() {
    if(!$this->target_model) $this->target_model = Inflections::camelize($this->field, true);
    // Overrides naming of field to model_id if col_name is not explicitly set
    if($this->col_name == $this->field){
      $link = new $this->target_model;
      $this->col_name = Inflections::underscore($this->target_model)."_".$link->primary_key;
    }
  }

  public function validate() {
    return true;
  }
  
  public function setup_validations() {
    if($this->required) $this->validations[]="required";
    if($this->unique) $this->validations[]="model_unique";
  }
  
  public function get() {
    $class = $this->target_model;
    if($cache = WaxModel::get_cache($class, $this->field, $this->model->primval)) return $cache;
    $model = new $this->target_model($this->model->{$this->col_name});
    if($model->primval) {
      WaxModel::set_cache($class, $this->field, $this->model->primval, $model);
      return $model;
    } else return false;
  }
  
  public function set($value) {
    if($value instanceof WaxModel) {
      $this->model->{$this->col_name} = $value->{$value->primary_key};
      return $this->model->save();
    } elseif(is_numeric($value)) {
      $this->model->{$this->col_name} = $value;
      return $this->model->save();
    }
    $class = get_class($this->model);
    WaxModel::unset_cache($class, $this->field, $this->model->{$this->col_name});
  }
  
  public function save() {
    return true;
    //return $this->set($this->value);
  }
  
  public function get_choices() {
    if($this->choices && $this->choices instanceof WaxRecordset) {
      foreach($this->choices as $row) $choices[$row->{$row->primary_key}]=$row->{$row->identifier};
      $this->choices = $choices;
      return true;
    }
    $this->link = new $this->target_model;
    WaxEvent::run("wax.choices.filter",$this); //filter choices hook
    $this->choices[""]="Select";
    foreach($this->link->all() as $row) $this->choices[$row->{$row->primary_key}]=$row->{$row->identifier};
    return $this->choices;
  }
  
  public function __get($name) {
    if($name == "value") return $this->model->{$this->col_name};
    return parent::__get($name);
  }


} 
