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
			$current_value = $this->model->{$this->col_name};
			$primary = $this->model->primary_key;
			//clone model to not mess with filters
			$model_name = get_class($this->model);
			$model = new $model_name;
			//find anything that matches this column value, make sure primay key is not this one
			$present = $model->filter("`".$this->col_name."`='".$current_value."'")->filter("`".$primary."` <> '".$this->model->$primary."'")->all();
			if($present->count() > 0) $this->add_error($this->field, sprintf($this->messages["unique"], $this->label));
		}
  }

}