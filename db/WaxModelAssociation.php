<?php

/**
 * WaxModelAssociation Extends Recordset class
 * Adds specific methods to associated model sets
 * 
 * This is only used by the join fields (many to many & has many) in order to delegate calls to 
 * the right model and insure the correct model type is returned.
 * @package PhpWax
 **/

class WaxModelAssociation extends WaxRecordset {
  
  public $target_model;
  public $owner_field;
  public $current_object; //used as a cache of the current object for lazy loading checking on valid call

  public function __construct(WaxModel $model, WaxModel $target_model, $rowset, $owner_field=false) {
    $this->rowset = $rowset;
    $this->model = $model;
    $this->target_model = $target_model;
    $this->owner_field = $owner_field;
  }
  
  public function offsetGet($offset) {
    if(is_numeric($this->rowset[$offset])){
      if(!$this->current_object){
        $model = get_class($this->target_model);
        $this->current_object = new $model($this->rowset[$offset]);
      }
      return $this->current_object;
    }else{
      if(!$this->rowset[$offset]) return false;
      $obj = clone $this->target_model;
      $obj->row = $this->rowset[$offset];
      return $obj;
    }
  }
  
  public function valid() {
    if(isset($this->rowset[$this->key]) && is_numeric($this->rowset[$this->key])){ //lazy loading
      $model = get_class($this->target_model);
      while($this->key < count($this->rowset)){
        $this->current_object = new $model($this->rowset[$this->key]);
        if($this->current_object->primval()) return true;
        $this->next();
      }
      return false;
    }else{ //normal loading
      if(isset($this->rowset[$this->key])) return true;
      return false;
    }
  }

  public function count() {
    if(is_numeric($this->rowset[0])){
      $check_count = clone $this->target_model;
      $check_count->select_columns = array($check_count->primary_key);
      $check_count->filter($check_count->primary_key,$this->rowset);
      return $check_count->all()->count();
    }else return parent::count();
  }

  public function __call($method, $args) {
    return call_user_func_array(array($this->model->get_col($this->owner_field), $method), $args);
  }
  
}