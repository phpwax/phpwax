<?php
/**
 * This is only used by the join fields (many to many & has many) in order to delegate calls to 
 * the right model and insure the correct model type is returned.
 */
class WaxModelCollection extends WaxRecordset {
  
  public $originating_model;
  public $field;
  public $model; // Target Model
  public $rowset = false;


  public function __construct($originating_model, $field, $model, $rowset=false) {
    $this->originating_model = new WaxModelProxy($originating_model);
    $this->field = $field;
    $this->model = $model;
    if($rowset){ 
      $this->rowset = $rowset;
    }
  }
  
  
  public function offsetGet($offset) {
    $this->load();
    if($this->rowset[$offset] instanceof WaxModelProxy) return $this->rowset[$offset]->get();
    else return parent::offsetGet($offset);
  }
  
  public function offsetExists($offset) {
    $this->load();
    return parent::offsetExists($offset);
  }
  public function offsetSet($offset, $value) {
    $this->load();
    parent::offsetSet($offset, $value);
  }
  
  public function valid() {
    $this->load();   
    return parent::valid();
  }
  
  public function count() {
    $this->load();
    return parent::count();
  }
  
  
  public function __call($method, $args) {
    $col = $this->originating_model->get()->get_col($this->field);
    return call_user_func_array(array($col, $method), $args);
  }
  
  public function add(WaxModel $model) {
    $this->load();
    //reassigning so that changes in the model reflect correctly
    foreach($this as $index => $row) if($row->{$model->primary_key} && ($row->{$model->primary_key} == $model->pk())) $reassign = $index;
    if($reassign) $this->rowset[$reassign] = new WaxModelProxy($model);
    else $this->rowset[] = new WaxModelProxy($model);
  }
  
  public function load() {
    if($this->rowset !== false) return true;
    $field = $this->originating_model->get()->get_col($this->field)->join_field;
    $this->model->filter($field,$this->originating_model->get()->pk());
    $this->rowset = $this->model->rows();
  }
  
  
  

}
