<?php
/**
*  A generic interface for performing validations on an object.
*  
*/
class WaxValidate {
  
  public $object;
  public $attribute;
  public $validations = array();
  public $label;
  
  public $errors = array();
  //errors messages
  public $messages = array(
    "short"=>       "%s needs to be at least %d characters",
    "long"=>        "%s needs to be shorter than %d characters",
    "required"=>    "%s is a required field",
    "unique"=>      "%s has already been taken",
    "match"=>       "%s and %s do not match",
    "format"=>      "%s is not a valid %s format"
  );
  
  /**
   * The constructor takes an object which conforms to a validating interface.
   * An object needs to have the following attributes available:
   *    $object:      the object to be validated
   *    $attribute:   the value to be validated called by $object->attribute
   *    
   */
  public function __construct($object, $attribute) {
    $this->object= $object;
    $this->attribute = $attribute;
    if(!$object->label) $this->label = $attribute;
    else $this->label = $object->label;
  }

  protected function add_error($field, $message) {
    if(!in_array($message, (array)$this->errors)) $this->errors[]=$message;
 	}
 	
 	
 	/**
   *    $type:  the type of validation - the builtin options are:
   *            1.  length
   *            2.  float
   *            3.  required
   *            4.  match
   *            5.  format 
 	 */
  public function validate($type, $options=array()) {
    foreach($options as $option_key=>$option_val) {
      $this->$option_key = $option_val;
    }
    $this->validations[]=$type;
  }
  
  public function errors() {
    return $this->errors;
  }
  
  public function is_valid() {
    
  }
  
  /********* Validation Methods ********************/
  
  protected function valid_length() {
    if($this->minlength && strlen($this->model->{$this->field}) < $this->minlength) {
      $this->add_error($this->column, sprintf($this->messages["short"], $this->label, $this->minlength));
    }
    if($this->maxlength && strlen($this->model->{$this->field})> $this->maxlength) {
      $this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->maxlength));
    }
  }
  protected function valid_float(){
		$lengths = explode(",", $this->maxlength);
		$values = explode(".", $this->model->{$this->field});
		if(strlen($values[0]) > $lengths[0]){
			$this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->minlength));
		}
		if($values[1] && $lengths[1] && strlen($values[1]) > $lengths[1]){
			$this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->minlength));
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
  
  protected function valid_match($confirm_field, $confirm_name) {
    if($this->model->{$this->field} != $this->model->{$confirm_field}) {
      $this->add_error($this->field, sprintf($this->messages["match"], $this->label, $confirm_name));
    }
  }
  
}
