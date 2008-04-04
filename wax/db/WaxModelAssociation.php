<?php

/**
 *  WaxModelAssociation Extends Recordset class
 *  Adds specific methds to associated model sets
 *
 * @package PhpWax
 **/

class WaxModelAssociation extends WaxRecordset {
  
  public $field;
  public $target_model;
  public $model;

  public function __construct($target, $join_model) {
    parent::__construct($target, $target->all()->rowset);
    $this->field = $field;
    $this->target_model = $target;
    $this->model = $join_model;
  } 
  
  public function unlink($model) {
    if($model instanceof WaxModel) {
      $id = $model->primval;
      $this->model->filter(array($this->target_model->table."_".$this->target_model->primary_key => $id))->delete();
    }
    if($model instanceof WaxRecordset) {
      foreach($model as $obj) {
        $id = $obj->primval;
        $filter[]= $this->target_model->table."_".$this->target_model->primary_key."=".  $id;
      }
      $res = $this->model->filter("(".join(" OR ", $filter).")")->delete()->all();
    }
    return $res;
  }
  

  
}