<?php
/**
 * This is only used by the join fields (many to many & has many) in order to delegate calls to 
 * the right model and insure the correct model type is returned.
 */
class WaxModelCollection extends WaxRecordset {
  
  public $originating_model;
  public $field;
  public $target_model;

  public function __construct($originating_model, $field, $target_model, $rowset) {
    $this->originating_model = $originating_model;
    $this->field = $field;
    $this->target_model = $target_model;
    $this->rowset = $rowset;
  }
  
  public function offsetGet($offset) {
    if(is_numeric($this->rowset[$offset])){
      if(!$this->current_object){
        $this->current_object = new $this->target_model($this->rowset[$offset]);
      }
      return $this->current_object;
    }else{
      if(!$this->rowset[$offset]) return false;
      $obj = clone $this->target_model;
      $obj->row = $this->rowset[$offset];
      return $obj;
    }
  }
  
  public function __call($method, $args) {
    $model = new $this->originating_model;
    return call_user_func_array(array($model->get_col($this->field), $method), $args);
  }

}