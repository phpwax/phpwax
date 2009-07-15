<?php

/**
 *  Recordset class
 *  Allows array-like access to a data set
 *
 * @package PhpWax
 **/

class WaxRecordset implements Iterator, ArrayAccess, Countable {

  protected $model = false;
  protected $obj = false;
  protected $key = 0;
  protected $constraints = array();
  public $rowset;
  
  public function __construct(WaxModel $model, $rowset) {
    $this->rowset = $rowset;
    $this->model = $model;
  }
  
  public function next() {
    $this->key++;
  }
  
  public function current() {
    return $this->offsetGet($this->key);
  }
  
  public function key() {
    return $this->key;
  }
  
  public function rewind() {
    $this->key=0;
  }
  
  public function valid() {
    if($this->rowset[$this->key]) return true;
    return false;
  }
  
  public function offsetExists($offset) {
    if($this->rowset[$offset]) return true;
    return false;
  }
  
  public function offsetGet($offset) {
    if(is_numeric($this->rowset[$offset])){
      $model = get_class($this->model);
      return new $model($this->rowset[$offset]);
    }else{
      $obj = clone $this->model;
      $obj->row = $this->rowset[$offset];
      return $obj;
    }
  }
  
  public function offsetSet($offset, $value) {
    $this->rowset[$offset]=$value;
  }
  
  public function offsetUnset($offset) {
    if(is_array($this->rowset)) array_splice($this->rowset, $offset,1);
  }
  
  public function count() {return count($this->rowset);}
  
  public function __call($method, $args) {
    return call_user_func_array(array($this->model, $method), $args);
  }
  
}