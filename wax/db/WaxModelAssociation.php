<?php

/**
 *  WaxModelAssociation Extends Recordset class
 *  Adds specific methods to associated model sets
 *
 * @package PhpWax
 **/

class WaxModelAssociation extends WaxRecordset {
  
  public $target_model;
  public $model;
  public $owner_field;

  public function __construct($target, $join_model, $owner_field=false) {
    parent::__construct($target, $target->all()->rowset);
    $this->target_model = $target;
    $this->model = $join_model;
    $this->owner_field = $owner_field;
  } 
  
  public function offsetGet($offset) {
     $obj = clone $this->target_model;
     $obj->set_attributes($this->rowset[$offset]);
     return $obj;
   }
  
  
  public function __call($method, $args) {
    return call_user_func_array(array($this->model->get_col($this->owner_field), $method), $args);
  }

  
}