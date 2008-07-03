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

  public function __construct(WaxModel $model, WaxModel $target_model, $rowset, $owner_field=false) {
    $this->rowset = $rowset;
    $this->model = $model;
    $this->target_model = $target_model;
    $this->owner_field = $owner_field;
  }
  
  public function offsetGet($offset) {
    $model = get_class($this->target_model);
    if(is_numeric($this->rowset[$offset])) return new $model($this->rowset[$offset]);
    $obj = new $model();
    $obj->set_attributes($this->rowset[$offset]);
    return $obj;
  }

  public function __call($method, $args) {
    return call_user_func_array(array($this->model->get_col($this->owner_field), $method), $args);
  }
  
}