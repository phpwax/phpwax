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

  public function __construct($target, $join_model, $owner_field=false) {
    $this->rowset = $target->all()->rowset;
    $this->model = $target;
    $this->join_model = $join_model;
    $this->owner_field = $owner_field;
  }
  
  public function __call($method, $args) {
    return call_user_func_array(array($this->join_model->get_col($this->owner_field), $method), $args);
  }

  
}