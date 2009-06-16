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
   *    $type:  the type of validation - the built-in options are:
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
    foreach($this->validations as $name){
      $func = "valid_".$name;
      $this->$func();
    }
    if(count($this->errors)) return false;
    else return true;
  }
  
  /********* Validation Methods ********************/
  
  protected function valid_length() {
    
    $value = $this->object->value();   
    if($this->minlength && strlen($value) < $this->minlength) {
      $this->add_error($this->label, sprintf($this->messages["short"], $this->label, $this->minlength));
    }
    if($this->maxlength && strlen($value)> $this->maxlength) {
      $this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->maxlength));
    }
  }
  protected function valid_float(){
    $value = $this->object->value();
		$lengths = explode(",", $this->maxlength);
		$values = explode(".", $value);
		if(strlen($values[0]) > $lengths[0]){
			$this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->minlength));
		}
		if($values[1] && $lengths[1] && strlen($values[1]) > $lengths[1]){
			$this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->minlength));
		}
	}

  protected function valid_email(){
    $this->regex_pattern = '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-\+]+)*@[a-zA-Z0-9-]+(\.[a-zA-z0-9-]+)*(\.[a-zA-Z]{2,4})$/i';
    return $this->valid_format();
  }
  protected function valid_format() {
    $value = $this->object->value();
    if(!preg_match($this->regex_pattern, $value)) {
      $this->add_error($this->column, sprintf($this->messages["format"], $this->label, $name));
		}
  }
  
  protected function valid_required() {
    $value = $this->object->value();
    if(strlen($value)< 1) $this->add_error($this->field, sprintf($this->messages["required"], $this->label));
  }
  
  //check that the submit button has a value - but a blank error so no front end error is shown
  protected function valid_submission(){
    if(strlen($this->object->value())< 1) $this->add_error($this->field, sprintf("", $this->label));
  }
  
  protected function valid_match($confirm_field, $confirm_name) {
    $value = $this->object->value();
    if($this->model->{$this->field} != $this->model->{$confirm_field}) {
      $this->add_error($this->field, sprintf($this->messages["match"], $this->label, $confirm_name));
    }
  }
  
}
