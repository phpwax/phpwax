<?php
class TestModel extends WaxModel {
  
}

class TestWaxModel extends WXTestCase {
    public function setUp() {
      $this->model = new TestModel();
    }
    
    public function tearDown() {

    }
    
    
    public function get_fixture($type) {
      $fixtures = array(
        "user1" => array("username"=>"test1", "password"=>"password", "email"=>"test1@test.com"),
        "user2" => array("username"=>"test2", "password"=>"password", "email"=>"test2@test.com"),
        "user3" => array("username"=>"test1", "password"=>"password", "email"=>"test3@test.com")
      );
      return $fixtures[$type];
    }
    
    
}

