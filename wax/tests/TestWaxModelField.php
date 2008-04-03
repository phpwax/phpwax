<?php

class TestWaxModelField extends WXTestCase {
    public function setUp() {
      $this->model = new Example();
      $this->model_owner = new ExampleOwner();
      $this->model->syncdb();
      $this->model_owner->syncdb();
      $model3 = new ExampleProperty;
      $model3->syncdb();
    }
    
    public function tearDown() {
      $model1 = new Example;
      $model1->delete();
      $model2 = new ExampleOwner;
      $model2->delete();
      $model3 = new ExampleProperty;
      $model3->delete();
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
      $this->assertEqual("Master", $model->example_owner->name);
    }
    
    public function test_has_many() {
      $owner = $this->model_owner->create(array("name"=>"Master"));
      $model = $this->model->create($this->get_fixture("user1"));
      $model2 = $this->model->create($this->get_fixture("user2"));
      $model->example_owner = $owner;
      $model2->example_owner = $owner;
      $this->assertEqual($owner->examples->count(), 2);
    }
    
    public function test_many_many() {
      $model = $this->model->create($this->get_fixture("user1"));
      $props = new ExampleProperty;
      
      $prop1 = $props->create(array("name"=>"Property 1"));
      $prop2 = $props->create(array("name"=>"Property 2"));
      $model->properties = $props->all();
      $this->assertIsA($model->properties, "WaxModelAssociation");
      $this->assertEqual($model->properties->count(), 2);
      $model->properties->unlink($prop1);
      $this->assertEqual($model->properties->count(), 1);
    }

    
}







