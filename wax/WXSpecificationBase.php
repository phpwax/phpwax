<?php
class WXSpecificationBase extends WXValidations
{
	private $specifications = array();
	private $tests = array();
	
		
	public function __construct() {

	}
	
	public function is_satisfied_by($class, $specification) {
		foreach($class as $class_var) {
			$this->tests[]=$class_val;
		}
		foreach($specification as $spec) {
			$this->specifications[]=$spec;
		}
	}
	
	
}

?>