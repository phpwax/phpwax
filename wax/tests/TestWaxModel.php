<?php
class Example extends WaxModel {
  public function setup() {
    $this->define("username", "CharField", array("maxlength"=>40));
    $this->define("password", "CharField", array("blank"=>false, "maxlength"=>15));
    $this->define("email", "EmailField", array("blank"=>false));
  }
}

class TestWaxModel extends WXTestCase {
    public function setUp() {
      $this->model = new Example;
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
      $this->assertIsA($res, "WaxModel");
      $this->assertEqual($res->username, "test1");
    }
    
    public function test_delete() {
      $res = $this->model->filter(array("username"=>"test1"))->all()->delete();
      $res = $this->model->filter(array("username"=>"test1"))->first();
      $this->assertFalse($res->count(), "0");
    }
    
    public function test_multiple_delete() {
      $this->model->create($this->get_fixture("user1"));
      $this->model->create($this->get_fixture("user2"));
      $this->assertEqual($this->model->all()->count(), "2");
      $res = $this->model->all()->delete();
      $this->assertEqual($this->model->all()->count(), "0");
    }
    
    public function test_update() {
      $this->model->create($this->get_fixture("user1"))->update_attributes(array("username"=>"altered"));
      $res = $this->model->filter(array("username"=>"altered"))->all();
      $this->assertEqual($res->count(), "1");
      $this->model->clear()->delete();
    }
    
    public function test_multiple_filters() {
      $this->model->create($this->get_fixture("user2"))->update_attributes(array("username"=>"altered"));
      $this->model->create($this->get_fixture("user3"));
      $res = $this->model->filter(array("password"=>"password"))->all()->filter("username !='altered'")->all();
      $this->assertEqual($res->count(), "2");
      $this->model->clear()->delete();
    }
    
}







