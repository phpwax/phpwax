<?php

class WXValidations
{
  const EMAIL =                   '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
  const UK_POSTCODE =             '/^(GIR0AA)|(TDCU1ZZ)|((([A-PR-UWYZ][0-9][0-9]?)|(([A-PR-UWYZ][A-HK-Y][0-9][0-9]?)|(([A-PR-UWYZ][0-9][A-HJKSTUW])|([A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVWXY]))))[0-9][ABD-HJLNP-UW-Z]{2})$/';
  const USA_ZIPCODE =             '/[[:digit:]]{5}(-[[:digit:]]{4})?/';
  const USA_DATE =                '/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/';
  const UK_DATE =                 '/([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})/';
  const NUMBER =                  '/^[+-]?[0-9]*\.?[0-9]+$/';
  const INTEGER =                 '/^[0-9]*$/';
  const PRINTABLE =               '/^[[:print:]]{1}/';       
              	                        
	protected $extra_validations = array();
	protected $validations = array();
	protected static $errors = array();
	
	
	
	/**
	 * getter method to retrieve a value from the set array or 
	 * the customisable extras array. In the event that neither
	 * are found it returns false
	 */
	function __get($field)
	{  	
  	if(isset($this->extra_validations[$field])) {
      return $this->extra_validations[$field];	
  	} elseif(defined(self::strtoupper($field))) {
      return self::strtoupper($field);		
  	} else {
      return false;	
  	}
	}
	
	/**
	 * setter method to alter the values of either exisiting
	 * values in the private arrays or add a new field into 
	 * the extras array.
	 */
	function __set($field, $value)
	{
  	if(isset($this->extra_validations[$field])) {
      $this->extra_validations[$field] = $value;	
  	} elseif(isset($this->fixed[$field])) {
      $this->fixed_validations[$field] = $value;		
  	} else {
      $this->extra_validations[$field] = $value;
  	}
	}
	
	public function get_errors() {
		$ret = self::$errors;
		self::$errors=array();
		return $ret;
	}
	
	protected function validate() {
		if(count(self::$errors) <1) {
			return true;
		}
		return false;
	}
	
	public function add_error($field, $message) {
		
		self::$errors[]=array("field"=>$field, "message"=>$message);
	}
	
	protected function valid_format($field, $format, $message="is an invalid format", $optional=true, $max_length=0) {
		if(strlen($this->{$field})<1) {
			$this->valid_required($field);
			return false;
		}
		$format = strtoupper($format);
		if(defined('self::'.$format)) {
			$format = constant('self::'.$format);
		}
		if($max_length > 0){
			self::valid_length($field, 0, $max_length);
		}
		echo $format; exit;
		if(preg_match($format, $this->{$field})) {
			return true;
		} else {
			$this->add_error($field, $message);
			return false;
		}
	}
	
	protected function valid_required($field, $message="is a required field") {
		if(strlen($this->{$field}) < 1 ) {
			$this->add_error($field, $message);
			return false;
		}
	}
	
	protected function valid_length($field, $min, $max=0, $message_short="must be at least", $message_long="must be less than") {
		if(strlen($this->{$field}) < $min && $min >0 ) {
			$this->add_error($fieginld, $message_short." {$min} characters");
			return false;
		}
		if(strlen($this->{$field}) > $max && $max > 0 ) {
			$this->add_error($field, $message_long." {$max} characters");
			return false;
		}
	}
	
	protected function valid_unique($field, $message="is already taken") {
		if(!$this->{$field}) {
			return false;
		}
		$class = get_class($this);
		$obj = new $class;
		$res = $obj->find_all(array("conditions"=>"{$field}='{$this->{$field}}'"));
		foreach($res as $row) {
		  if($row->id != $this->id) {
			  $this->add_error($field, $message);
			  return false;
		  }
		}
	}
	
	protected function valid_confirm($field1, $field2, $message="do not match") {
		if($this->{$field1} !== $this->{$field2}) {
			$this->add_error($field1." and ".$field2, $message);
			return false;
		}
	}
	
	protected function validations() {}
	
}