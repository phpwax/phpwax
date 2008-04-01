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
      $res = $this->model->create($this->get_fixture("user1"));
      $this->assertIsA($res, "WaxModel");
      $this->assertEqual($res->username, "test1");
    }
    
    public function test_all() {
      $res = $this->model->all();
      $this->assertIsA($res, "WaxRecordset");
    }
    
    public function test_first() {
      $res = $this->model->first();
      print_r($res); exit;
      $this->assertIsA($res, "WaxModel");
      $this->assertEqual($res->username, "test1");
    }
    
}

