<?php
namespace Wax\Form;

/**
*  A generic interface for performing validations on an object.
*  
*/
class Validate {
  
  public $object;
  public $attribute;
  public $validations = array();
  public $label;
  public $formats = array(
    "email"=>       '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-\+]+)*@[a-zA-Z0-9-]+(\.[a-zA-z0-9-]+)*(\.[a-zA-Z]{2,4})$/i',
    "datetime"=>    '/^([0-9-]{4}-[0-9]{2}-[0-9]{2}\s{1}[0-9]{2}:[0-9]{2}:[0-9]{2}|[0-9-]{4}-[0-9]{2}-[0-9]{2})$/',
    "number"=>      '/^[0-9]*/',
    "boolean"=>     '/^[0-1]?$/' 
  );
  
  public $errors = array();
  //errors messages
  public $messages = array(
    "short"=>       "%s needs to be at least %d characters",
    "long"=>        "%s needs to be shorter than %d characters",
    "required"=>    "%s is a required field",
    "unique"=>      "%s has already been taken",
    "match"=>       "%s and %s do not match",
    "format"=>      "%s is not a valid %s format",
    "checked"=>      "%s needs to be checked"
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
  public function add_validation($type, $options=array()) {
    foreach($options as $option_key=>$option_val) {
      $this->$option_key = $option_val;
    }
    $this->validations[]=$type;
  }
  
  public function errors() {
    return $this->errors;
  }
  
  public function validate() {
    $this->validations = array_unique($this->validations);
    foreach($this->validations as $name){
      $func = "valid_".$name;
      if(method_exists($this, $func)) $this->$func();
    }
  }
  
  public function is_valid() {
    if(count($this->errors)) return false;
    else return true;
  }
  
  /********* Validation Methods ********************/
  protected function valid_model_unique(){
    //if this isnt a wax model & this is being called by mistake, return true
    if(! $this->object->model instanceOf WaxModel) return true;
    $field = $this->object->field;
    $value = $this->object->value();    
    $class = get_class($this->object->model);
    $primary_field = $this->object->model->primary_key;
    $primary_key = $this->object->model->primval();
    $model= new $class; 
    if($primary_key) $model->filter($primary_field, $primary_key, "!=");
    if($model->filter($field, $value)->first()) $this->add_error($this->label, sprintf($this->messages["unique"], $this->label));
  }
  
  
  protected function valid_length() {
    $value = $this->object->value();   
    if($this->object->minlength && strlen($value) < $this->object->minlength) {
      $this->add_error($this->label, sprintf($this->messages["short"], $this->label, $this->object->minlength));
    }
    if($this->object->maxlength && strlen($value)> $this->object->maxlength) {
      $this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->object->maxlength));
    }
  }
  
  protected function valid_checked() {
    $value = $this->object->value();
    if($value < 1){
			$this->add_error($this->column, sprintf($this->messages["checked"], $this->label));
		}
  }
  
  protected function valid_float(){
    $value = $this->object->value();
		$lengths = explode(",", $this->object->maxlength);
		$values = explode(".", $value);
		if(strlen($values[0]) > $lengths[0]){
			$this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->object->minlength));
		}
		if($values[1] && $lengths[1] && strlen($values[1]) > $lengths[1]){
			$this->add_error($this->column, sprintf($this->messages["long"], $this->label, $this->object->minlength));
		}
	}

  protected function valid_email(){
    if($this->object->value()) return $this->valid_format("email");
  }
  protected function valid_format($name, $string=false) {
    $value = $this->object->value();
    if(!$string) $string = $this->formats[$name];
    if(!preg_match($string, $value)) {
      $this->add_error($this->column, sprintf($this->messages["format"], $this->label, $name));
		}
  }
  
  protected function valid_datetime() {
    if($this->object->required === true) return $this->valid_format("datetime");
    else return true;
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
