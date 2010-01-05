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
  public $rowset;

  public function __construct($originating_model, $target_model, $rowset) {
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

  
  
}