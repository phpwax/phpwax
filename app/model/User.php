<?php
class User extends WXActiveRecord {
	public function specifications() {
		$this->add_spec('name', true);
		$this->add_spec('email', WXSpec::email);
	}
	
}

?>