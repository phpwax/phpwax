<?php
namespace Wax\Model;
use Wax\Core\ObjectProxy;

/**
 * ModelAssociation Extends Recordset class
 * Adds specific methods to associated model sets
 * 
 * This is only used by the join fields (many to many & has many) in order to delegate calls to 
 * the right model and ensure the correct model type is returned.
 * @package phpwax
 **/

class Association extends Recordset {
  
  public $target_model;
  public $owner_field;
  public $current_object; //used as a cache of the current object for lazy loading checking on valid call

  public function __construct(Model $model, Model $target_model, $rowset, $owner_field=false) {
    $this->rowset = $rowset;
    $this->model = new ObjectProxy($model);
    $this->target_model = new ObjectProxy($target_model);
    $this->owner_field = $owner_field;
  }
  
  public function offsetGet($offset) {
    return $this->rowset[$offset]->get();
  }
  
  public function valid() {
    if(isset($this->rowset[$this->key])) return true;
    return false;
  }

  public function count() {
    return count($this->rowset);
  }

  public function __call($method, $args) {
    return call_user_func_array(array($this->model->get_col($this->owner_field), $method), $args);
  }
  
}