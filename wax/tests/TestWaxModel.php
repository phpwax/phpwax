<?php
class ExampleModel extends WaxModel {
  public function setup() {
    $this->define("username", "CharField", array("maxlength"=>40));
    $this->define("password", "CharField", array("blank"=>false, "maxlength"=>15));
    $this->define("email", "EmailField", array("blank"=>false));
  }
}

class TestWaxModel extends WXTestCase {
    public function setUp() {
      $this->model = new ExampleModel();
      $this->model->syncdb();
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
    
    public function test_create() {
      $this->model->create($this->get_fixture("user1"));
      $res = $this->model->first();
      $this->assertIsA($res, "WaxModel");
    }
    
    
}

