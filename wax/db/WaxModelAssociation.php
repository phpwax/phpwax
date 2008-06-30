<?php

/**
 *  WaxModelAssociation Extends Recordset class
 *  Adds specific methods to associated model sets
 *
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
  }

  public function __call($method, $args) {
    error_log("Looking for ".$this->owner_field." on ".get_class($this->model));
    return call_user_func_array(array($this->model->get_col($this->owner_field), $method), $args);
  }
  
}