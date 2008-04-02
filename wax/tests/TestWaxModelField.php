<?php
class ExampleOwner extends WaxModel {
  
  public function setup() {
    $this->define("name", "CharField", array("maxlength"=>40));
    $this->define("examples", "HasManyField", array("model_name"=>"example"));
  }
}

class TestWaxModelField extends WXTestCase {
    public function setUp() {
      $this->model = new Example();
      $this->model_owner = new ExampleOwner();
      $this->model->syncdb();
      $this->model_owner->syncdb();
    }
    
    public function tearDown() {
      $this->model->clear()->delete();
      $this->model_owner->clear()->delete();
    }
    
    public function get_fixture($type) {
      $fixtures = array(
        "user1" => array("username"=>"test1", "password"=>"password", "email"=>"test1@test.com"),
        "user2" => array("username"=>"test2", "password"=>"password", "email"=>"test2@test.com"),
        "user3" => array("username"=>"test1", "password"=>"password", "email"=>"test3@test.com")
      );
      return $fixtures[$type];
    }
    
    public function test_get_field() {
      $res = $this->model->create($this->get_fixture("user1"));
      $this->assertEqual($res->username, "test1");
    }
    
    public function test_validate_length() {
      $this->model->define("username", "CharField", array("maxlength"=>"3"));
      $res = $this->model->set_attributes($this->get_fixture("user1"));
      $this->assertFalse($res->validate());

      $res = new Example;
      $res->define("username", "CharField", array("maxlength"=>"6"));
      $res->set_attributes($this->get_fixture("user1"));
      $this->assertTrue($res->validate());
      
      $res = new Example;
      $res->define("username", "CharField", array("minlength"=>"6"));
      $res->set_attributes($this->get_fixture("user1"));
      $this->assertFalse($res->validate());
      $this->assertFalse($res->save());
    }
    
    public function test_foreign_key() {
      $owner = $this->model_owner->create(array("name"=>"Master"));
      $model = $this->model->create($this->get_fixture("user1"));
      $model->example_owner = $owner;
      $this->assertEqual("test1", $model->username);
      //$this->assertEqual("Master", $model->example_owner->name);
    }
    
    public function test_has_many() {
      $owner = $this->model_owner->create(array("name"=>"Master"));
      $model = $this->model->create($this->get_fixture("user1"));
      $model2 = $this->model->create($this->get_fixture("user2"));
      $model->example_owner = $owner;
      $model2->example_owner = $owner;
      $this->assertEqual($owner->examples->count(), 2);
    }

    
}







