<?php
class User extends WXActiveRecord 
{
	var $children = array("article");
	
	public function specifications() {
		$this->add_spec('name', true);
		$this->add_spec('email', WXSpec::email);
	}
	
}

?>