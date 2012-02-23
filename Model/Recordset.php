<?php
namespace Wax\Model;

/**
 *  Recordset class
 *  Allows array-like access to a data set
 *
 * @package PhpWax
 **/

class Recordset implements \Iterator, \ArrayAccess, \Countable {

  public $model = false;
  public $rowset;
  
  protected $obj = false;
  protected $key = 0;
  
  
  public function __construct($model, $rowset) {
    $this->rowset = $rowset;
    $this->model = $model;
  }
  
  public function first() {
    return $this[0];
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
    if(isset($this->rowset[$this->key])) return true;
    return false;
  }
  
  public function offsetExists($offset) {
    if($this->rowset[$offset]) return true;
    return false;
  }
  
  public function offsetGet($offset) {
    if($this->rowset[$offset] instanceof ObjectProxy) return $this->rowset[$offset]->get();
    if($this->model && ($obj = clone $this->model)){
      $obj->row = $this->rowset[$offset];
      return $obj;
    }
    return $this->rowset[$offset];
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