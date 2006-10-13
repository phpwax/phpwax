<?php

class WXValidations
{
  /**
   * constant variables used for regular expression validation via eregi
   * regex patterns for: email; uk postcode; usa zipcode usa date; uk date; 
   * numbers; currency; uk telephone numbers; printable text; national 
   * insurance number.
   *	
	*/
	protected $fixed_validations = array(
		'email'                   	=> '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
	  'uk_postcode'               => '/^(GIR0AA)|(TDCU1ZZ)|((([A-PR-UWYZ][0-9][0-9]?)|(([A-PR-UWYZ][A-HK-Y][0-9][0-9]?)|(([A-PR-UWYZ][0-9][A-HJKSTUW])|([A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVWXY]))))[0-9][ABD-HJLNP-UW-Z]{2})$/',
	  'usa_zipcode'               => '/[[:digit:]]{5}(-[[:digit:]]{4})?/',
	  'usa_date'                  => '/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/',
	  'uk_date'                   => '/([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})/',
	  'number'                    => '/^[+-]?[0-9]*\.?[0-9]+$/',
	  'currency'                  => '/^\$?([1-9]{1}[0-9]{0,2}(\,[0-9]{3})*(\.[0-9]{0,2})?|[1-9]{1}[0-9]{0,}(\.[0-9]{0,2})?|0(\.[0-9]{0,2})?|(\.[0-9]{1,2})?)$/',
    'simple_phone_number'       => '/^[[:digit:]]{6,20}/',
    'printable'                 => '/^[[:print:]]{1}/',
    'national_insurance_number' => '/^[A-CEGHJ-PR-TW-Z]{1}[A-CEGHJ-NPR-TW-Z]{1}[0-9]{6}[A-DFM]{0,1}$/'
  );
              	                        
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
  	} elseif(isset($this->fixed[$field])) {
      return $this->fixed_validations[$field];		
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
		return self::$errors;
	}
	
	protected function validate() {
		if(count(self::$errors) <1) {
			return true;
		}
		return false;
	}
	
	protected function add_error($field, $message) {
		self::$errors[]=array("field"=>$field, "message"=>$message);
	}
	
	protected function valid_format($field, $format, $message="is an invalid format", $optional=true) {
		if(strlen($this->{$field})<1) {
			$this->valid_required($field);
			return false;
		}
		if(array_key_exists($format, $this->fixed_validations)) {
			$format = $this->fixed_validations[$format];
		}
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
			$this->add_error($field, $message_short." {$min} characters");
			return false;
		}
		if(strlen($this->{$field}) > $max && $max > 0 ) {
			$this->add_error($field, $message_long." {$max} characters");
			return false;
		}
	}
	
	protected function validations() {}
	
}