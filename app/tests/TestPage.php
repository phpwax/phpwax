<?php

class TestPage extends WXTestCase
{
	public function test_user_is_found() {
		$user = new User();
		$this_user = $user->find_first();
		$this->assertEquals($this_user->username, "rossriley");
	}

}

?>