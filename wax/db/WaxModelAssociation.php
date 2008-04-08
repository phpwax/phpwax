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
  } 
  
  public function unlink($model) {
    return $this->model->get_col($this->owner_field)->unlink($model);
  }
  

  
}