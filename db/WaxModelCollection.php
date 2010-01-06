<?php

/**
 * WaxModelAssociation Extends Recordset class
 * Adds specific methods to associated model sets
 * 
 * This is only used by the join fields (many to many & has many) in order to delegate calls to 
 * the right model and insure the correct model type is returned.
 * @package PhpWax
 **/

class WaxModelCollection extends WaxRecordset {
  
  public $originating_model;
  public $target_model;
  public $through_model;
  public $through_table;
  public $rowset;

  public function __construct($originating_model, $target_model, $rowset, $through_model = false, $through_table = false) {
    $this->rowset = $rowset;
    $this->originating_model = $originating_model;
    $this->target_model = $target_model;
  }
  
  public function offsetGet($offset) {
    if(is_numeric($this->rowset[$offset])){
      if(!$this->current_object){
        $model = $this->target_model;
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
  
  /**
   * saves unsaved rows in the set, and for ManyToMany saves the join table rows too
   *
   * @return void
   * @author Sheldon Els
   */
  public function save_assocations($originating_model_primary_key){
    $originating_model = new $this->originating_model;
    $target_model = new $this->target_model;
    foreach($this->rowset as $row)
      if(!$row[$target_model->primary_key]){
        $target_model->row = $row;
        $target_model->save();
      }
    if($this->through_model){
      $through_model = new $this->through_model;
      $this->through_model->init($originating_model,$target_model);
      if($this->through_table) $through_model->table = $this->through_table;
      foreach($this->rowset as $row){
        $through_model->row = $row;
        $through_model->save();
      }
    }
  }
}