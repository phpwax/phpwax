<?php
class User extends WXActiveRecord 
{
	public function validations() {
		$this->valid_format("email", "email", false);
		$this->valid_required("name");
		//$this->valid_unique("email", "email");
	}
	
}

?>