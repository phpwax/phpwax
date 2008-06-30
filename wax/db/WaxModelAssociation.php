<?php

/**
 *  WaxModelAssociation Extends Recordset class
 *  Adds specific methods to associated model sets
 *
 * @package PhpWax
 **/

class WaxModelAssociation extends WaxRecordset {
  
  public $join_model;
  public $owner_field;

  public function __construct(WaxModel $model, WaxModel $join_model, $rowset, $owner_field=false) {
    $this->rowset = $rowset;
    $this->model = $model;
    $this->join_model = $join_model;
    $this->owner_field = $owner_field;
  }
  
  public function offsetGet($offset) {
    $model = get_class($this->join_model);
    if(is_numeric($this->rowset[$offset])) return new $model($this->rowset[$offset]);
  }

  public function __call($method, $args) {
    error_log("Looking for ".$this->owner_field." on ".get_class($this->target_model));
    return call_user_func_array(array($this->target_model->get_col($this->owner_field), $method), $args);
  }
  
}