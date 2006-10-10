<?php
class WXSpecificationBase extends WXValidations
{
	public $specification = null;
		
	public function is_satisfied_by($value, $specification) {
		if($value === $specification) {
			return true;
		} else {
			return false;
		}
	}
	
	
}


?>