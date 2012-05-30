<?php
namespace Wax\Model;
use Wax\Core\ObjectProxy;

/**
 *  Recordset class
 *  Allows array-like access to a data set
 *
 * @package PhpWax
 **/

class Recordset implements \Iterator, \ArrayAccess, \Countable {

  public $model = FALSE;
  public $rowset;  
  protected $key = 0;
  
  
  public function __construct() {
    $args = func_get_args();
    if($args[0] instanceof Model) {
      $this->model =  $args[0];
      $this->initialise($args[1]);
    } else {
      $this->initialise($args[0]);
    }
  }
  
  public function dump() {
    foreach($this->rowset as $obj) {
      print_r($obj->get());
    }
  }
  
  public function initialise($rows) {
    foreach($rows as $row) {
      $this->rowset[] = new ObjectProxy($row);
    }
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
    return $this->rowset[$offset];
  }
  
  public function offsetSet($offset, $value) {
    $this->rowset[$offset]= new ObjectProxy($value);
  }
  
  public function offsetUnset($offset) {
    if(is_array($this->rowset)) array_splice($this->rowset, $offset,1);
  }
  
  public function count() {return count($this->rowset);}
  
  public function __call($method, $args) {
    return call_user_func_array(array($this->model, $method), $args);
  }

  
  
  
}