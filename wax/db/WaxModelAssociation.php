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

  public function __construct($target, $join_model) {
    parent::__construct($target, $target->all()->rowset);
    $this->target_model = $target;
    $this->model = $join_model;
  } 
  
  public function unlink($model) {
    
    return $this->field->unlink($model);
  }
  

  
}