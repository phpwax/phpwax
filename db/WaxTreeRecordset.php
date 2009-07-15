<?php

/**
 *  Recordset class
 *  Allows array-like access to a data set
 *
 * @package PhpWax
 **/

class WaxTreeRecordset extends WaxRecordset implements RecursiveIterator {
  
  protected $model = false;
  protected $obj = false;
  protected $constraints = array();
  public $key = 0;
  public $rowset;
  public $children = false;
  
  public function __construct(WaxModel $model, $rowset) {
    $this->rowset = $rowset;
    $this->model = $model;
  }
  
  public function hasChildren() { 
    if(count($this->rowset[$this->key()]["children"]) >0) return true;
    return false;
  } 

  public function getChildren() { 
    $current = $this->current_row();
    return new WaxTreeRecordset($this->model, $current["children"]); 
  }
  
  
  public function current() {
    $obj = clone $this->model;
    $obj->row = $this->rowset[$this->key()];
    return $obj;
  }
  
}

?>