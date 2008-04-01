<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class WaxModelField {
    
  // Database Specific Configuration
  public $field = false;
  public $null = false;
  public $default = false;
  public $primary_key = false;
  
  //Validation & Format Options
  public $maxlength = false;
  public $minlength = false;
  public $choices = false;
  public $blank = true;
  public $label = false;
  public $help_text = false;
  public $widget="CharField";
  protected $model = false;
  
  public $errors = false;
  
  public $messages = array(
    "short"=>       "%s needs to be at least %d characters",
    "long"=>        "%s needs to be shorter than %d characters",
    "required"=>    "%s is a required field",
    "unique"=>      "%s has already been taken",
    "confirm"=>     "%s and %s do not match",
    "format"=>      "%s is not a valid %s format"
  );
  
  

  public function __construct($column, $model, $options = array()) {
    $this->model = $model;
    $this->field = $column;
    foreach($options as $option=>$val) $this->{$option} = $val;
    $this->setup();
  }
  
  
  public function setup() {}
  public function validate() {}
  public function output() {}
  
  protected function add_error($field, $message) {
 	  $this->errors[]=$message;
 	}
 	
 	
 	/**
 	 *  Default Validation Methods
 	 */
 	 
  protected function valid_length() {
    if($this->maxlength && strlen($this->model->{$this->field})< $this->minlength) {
      $this->add_error($this->column, sprintf($this->messages["short"], $this->label, $this->minlength));
    }
    if($this->minlength && strlen($this->model->{$this->field})> $this->maxlength) {
      $this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->maxlength));
    }
  }
  
  protected function valid_format($name, $pattern) {
    if(!preg_match($pattern, $this->model->{$this->field})) {
      $this->add_error($this->column, sprintf($this->messages["format"], $this->label, $name));
		}
  }
  
  protected function valid_required() {
    if(!$this->blank && strlen($this->model->{$this->field})< 1) {
      $this->add_error($this->field, sprintf($this->messages["required"], $this->label));
    }
  }
  
  protected function valid_confirm($confirm_field, $confirm_name) {
    if($this->model->{$this->field} != $this->model->{$confirm_field}) {
      $this->add_error($this->field, sprintf($this->messages["confirm"], $this->label, $confirm_name));
    }
  }
  

} // END class 

