<?php

/**
 *  WaxModelAssociation Extends Recordset class
 *  Adds specific methds to associated model sets
 *
 * @package PhpWax
 **/

class WaxModelAssociation extends WaxRecordset {

  public function __construct($model) {
    print_r($model);
    parent::__construct($model, $model->all()->rowset);
  } 
  
  public function unlink() {
    return $this;
  }
  

  
  
}