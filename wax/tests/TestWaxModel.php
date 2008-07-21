<?php
class Example extends WaxModel {
  public function setup() {
    $this->define("username", "CharField", array("maxlength"=>40));
    $this->define("password", "CharField", array("blank"=>false, "maxlength"=>15));
    $this->define("email", "EmailField", array("blank"=>false));
    $this->define("example_owner", "ForeignKey", array("null"=>true));
    $this->define("propertiesLazy", "ManyToManyField", array("target_model"=>"ExampleProperty","load"=>"lazy"));
    $this->define("propertiesEager", "ManyToManyField", array("target_model"=>"ExampleProperty","load"=>"eager"));
  }
}

class ExampleOwner extends WaxModel {
  
  public function setup() {
    $this->define("name", "CharField", array("maxlength"=>40));
    $this->define("examples", "HasManyField", array("target_model"=>"Example"));
  }
}

class ExampleEditor extends WaxModel {
  public function setup() {
    $this->define("name", "CharField", array("maxlength"=>40));
    $this->define("examples", "HasManyField", array("target_model"=>"Example"));
  }
}

class ExampleProperty extends WaxModel {
  public function setup() {
    $this->define("name", "CharField", array("maxlength"=>40));
    $this->define("examples", "ManyToManyField", array("target_model"=>"Example"));
  } 
}

class TestWaxModel extends WXTestCase {
    public function setup() {
      $this->model = new Example;
      $this->prop = new ExampleProperty;
      $this->model->syncdb();
			$this->prop->syncdb();
    }
    
    public function tearDown() {
      $this->model->db->drop_table($this->model->table);
			$this->prop->db->drop_table($this->prop->table);
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
      $res = $this->model->create($this->get_fixture("user1"));
      $res = $this->model->first();
      $this->assertIsA($res, "WaxModel");
      $this->assertEqual($res->username, "test1");
    }
    
    public function test_delete() {
      $res = $this->model->create($this->get_fixture("user1"));
      $res = $this->model->filter(array("username"=>"test1"))->all()->delete();
      $res = $this->model->filter(array("username"=>"test1"))->first();
      $this->dump($res);
      $this->assertFalse($res);
    }
    
    public function test_multiple_delete() {
      $this->model->create($this->get_fixture("user1"));
      $this->model->create($this->get_fixture("user2"));
      $this->assertEqual($this->model->all()->count(), "2");
      $res = $this->model->all()->delete();
      $this->assertEqual($this->model->all()->count(), "0");
    }
    
    public function test_update() {
      $res = $this->model->create($this->get_fixture("user1"));
      $res = $this->model->filter(array("username"=>"test1"))->all();
      $this->assertEqual($res->count(), "1");
      $this->model->clear()->delete();
    }
    
    public function test_multiple_filters() {
      $res = $this->model->create($this->get_fixture("user2"))->update_attributes(array("username"=>"altered"));
      $this->model->create($this->get_fixture("user3"));
      $res = $this->model->filter(array("password"=>"password"))->all()->filter("username !='altered'")->all();
      $this->assertEqual($res->count(), 1);
      $this->model->clear()->delete();
    }
    
    public function test_filter_security() {
      $this->model->create(array("username"=>"d'oh", "password"=>"password", "email"=>"test@example.com"));
      $res = $this->model->filter(array("username"=>"d'oh"))->first();
      $this->assertEqual($res->username, "d'oh");
      $res2 = $this->model->filter(array("username = ? AND password=?"=>array("d'oh", "password")))->first();
      $this->assertEqual($res2->username, "d'oh");
      $res3 = $this->model->filter(array("username"=>array("d'oh")))->first();
      $this->assertEqual($res3->username, "d'oh");
    }
		
    
}







