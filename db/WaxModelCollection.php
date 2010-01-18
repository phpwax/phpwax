<?php
/**
 * This is only used by the join fields (many to many & has many) in order to delegate calls to 
 * the right model and insure the correct model type is returned.
 */
class WaxModelCollection extends WaxRecordset {
  
  public $originating_model;
  public $field;
  public $target_model;

  public function __construct($originating_model, $field, $target_model, $rowset=false) {
    $this->originating_model = new WaxModelProxy($originating_model);
    $this->field = $field;
    $this->target_model = $target_model;
    if($rowset) $this->rowset = $rowset;
  }
  
  
  public function offsetGet($offset) {
    return $this->rowset[$offset]->get();
  }
  
  public function __call($method, $args) {
    $model = $this->originating_model->get();
    return call_user_func_array(array($model->get_col($this->field), $method), $args);
  }
  
  public function add(WaxModel $model) {
    $this->rowset[] = new WaxModelProxy($model);
  }
  

}
