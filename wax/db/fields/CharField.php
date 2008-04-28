<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class CharField extends WaxModelField {
  
  public $maxlength = "255";
  public $unique = false;
  
  public function setup() {
    
  }

  public function validate() {
    $this->valid_length();
 	  $this->valid_required();
 	  $this->valid_unique();
  }

  protected function valid_unique() {
    if($this->unique){
      $model_name = get_class($this->model);
      $model = new $model_name();
      //checks if the id in the database is the same as the id of the current row (will also pass if there's no entry in the database)
      if($res = $model->filter(array($this->field => $model->{$this->model->field}))->first()->id && $res->id != $this->model->id){
        $this->add_error($this->field, sprintf($this->messages["unique"], $this->label));
      }
    }
  }

}