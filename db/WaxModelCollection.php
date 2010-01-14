<?php
/**
 * This is only used by the join fields (many to many & has many) in order to delegate calls to 
 * the right model and insure the correct model type is returned.
 */
class WaxModelCollection extends WaxRecordset {
  
  public $originating_model;
  public $field;
  public $target_model;
  public $eager_loading = false;
  public $constraints = false;
  public $loaded = false;

  public function __construct($originating_model, $field, $target_model, $constraints, $eager_loading) {
    $this->originating_model = $originating_model;
    $this->field = $field;
    $this->target_model = $target_model;
    $this->constraints = $constraints;
    $this->eager_loading = $eager_loading;
  }
  
  private function construct_target(){
    $target = new $this->target_model;
    $target->filter($this->constraints);
    return $target;
  }
  
  private function rowset(){
    if($this->rowset) return $this->rowset;
    $target = $this->construct_target();
    if(!$eager_loading) $this->join_model->select_columns = array($target->primary_key);
    return $this->rowset = $target->rows();
  }
  
  public function offsetGet($offset) {
    $this->rowset();
    $obj = $this->construct_target();
    if(is_numeric($this->rowset[$offset])){
      $obj->{$obj->primary_key} = $this->rowset[$offset];
      return $obj->first();
    }else{
      if(!$this->rowset[$offset]) return false;
      $obj->row = $this->rowset[$offset];
      return $obj;
    }
  }
  
  public function __call($method, $args) {
    $args[] = &$this;
    $model = new $this->originating_model;
    return call_user_func_array(array($model->get_col($this->field), $method), $args);
  }
  

}
