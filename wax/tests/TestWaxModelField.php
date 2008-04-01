<?php
class ExampleModel extends WaxModel {
  public function setup() {
    $this->define("username", "CharField", array("maxlength"=>40));
    $this->define("password", "CharField", array("blank"=>false, "maxlength"=>15));
    $this->define("email", "EmailField", array("blank"=>false));
    $this->define("example_owner_model", "ForeignKey");
  }
}

class ExampleOwnerModel extends WaxModel {
  
  public function setup() {
    $this->define("name", "CharField", array("maxlength"=>40));
  }
}

class TestWaxModelField extends WXTestCase {
    public function setUp() {
      $this->model = new ExampleModel();
      $this->model_owner = new ExampleOwnerModel();
      $this->model->syncdb();
      $this->model_owner->syncdb();
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







