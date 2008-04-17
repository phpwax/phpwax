<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class WaxModelField {
    
  // Database Specific Configuration
  public $field = false;          // How this column is referred to
  public $null = true;           // Can column be null
  public $default = false;        
  public $primary_key = false;
  public $col_name;               // Actual name in the storage engine
  
  //Validation & Format Options
  public $maxlength = false;
  public $minlength = false;
  public $choices = false;
  public $editable = true; // Only editable options will be displayed in forms
  public $blank = true;
  public $label = true; // Set to false to never show labels
  public $help_text = false;
  public $widget="TextInput";
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
    foreach($options as $option=>$val) $this->{$option} = $val;
    if(!$this->field) $this->field = $column;
    if(!$this->col_name) $this->col_name = $this->field;
    $this->setup();
  }
  
  public function get() {
    return $this->model->row[$this->field];
  }
  
  public function set($value) {
    $this->model->row[$this->field]=$value;
  }
  
  public function before_sync() {}  
  public function setup() {}
  public function validate() {}
  public function save() {}
  
  public function output() {
    return $this->model->row[$this->field];
  }
  
  protected function add_error($field, $message) {
 	  $this->errors[]=$message;
 	}
 	
 	
 	/**
 	 *  Default Validation Methods
 	 */
 	 
  protected function valid_length() {
    if($this->minlength && strlen($this->model->{$this->field}) < $this->minlength) {
      $this->add_error($this->column, sprintf($this->messages["short"], $this->label, $this->minlength));
    }
    if($this->maxlength && strlen($this->model->{$this->field})> $this->maxlength) {
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

