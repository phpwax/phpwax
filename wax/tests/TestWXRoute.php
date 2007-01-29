<?php

class TestWXRoute extends WXTestCase {
  
  
  
  public function setUp() {
    $_GET['route']="admin/test/case";
    $this->config_array = array("default"=>"page", "login"=>"admin/login");
  }
  
  public function tearDown() {}

    
	public function test_map_routes() {

	}
	
	
	public function test_pick_controller() {

	}

	private function test_check_controller() {

	}

	public function test_read_actions() {	

	}
	
	public function controller_to_url() {

	}
  
  
}
?>