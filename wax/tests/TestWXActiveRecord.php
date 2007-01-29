<?php

class TestModel extends WXActiveRecord {
	public function __construct() {
	  parent::__construct();
	  $this->has_many("test_model2", "images");
	}
}
class TestModel2 extends WXActiveRecord {}
class TestARMigration extends WXMigrate {
  public function up() {
    $this->create_column("username");
    $this->create_column("password");
    $this->create_column("email");
    $this->create_table("test_model");
    $this->create_table("test_model2");
  }
  
  public function down() {
    $this->drop_table("test_model");
    $this->drop_table("test_model2");
    $this->drop_table("test_model_test_model2");
  }
}

class TestWXActiveRecord extends WXTestCase 
{
    public function setUp() {
      $migrate = new TestARMigration('quiet');
      $migrate->up();
      $this->model = new TestModel();
      $this->model1 = new TestModel();
      $this->model1->update_attributes($this->get_fixture("user1"));
      $this->model2 = new TestModel();
      $this->model2->update_attributes($this->get_fixture("user2"));
      $this->model3 = new TestModel();
      $this->model3->update_attributes($this->get_fixture("user3"));
    }
    
    public function tearDown() {
      $migrate = new TestARMigration('quiet');
      $migrate->down();
    }
    
    public function get_fixture($type) {
      $fixtures = array(
        "user1" => array("username"=>"test1", "password"=>"password", "email"=>"test1@test.com"),
        "user2" => array("username"=>"test2", "password"=>"password", "email"=>"test2@test.com"),
        "user3" => array("username"=>"test1", "password"=>"password", "email"=>"test3@test.com")
      );
      return $fixtures[$type];
    }
    
    public function test_setup() {
      $this->assertEqual($this->model1->username, "test1");
      $this->assertEqual($this->model2->username, "test2");
      $this->assertEqual($this->model3->username, "test1");
    }
    
    public function test_finders() {
      $search = $this->model->find(1);
      $this->assertIsA($search, "WXActiveRecord");
      $search = $this->model->find_all();
      $this->assertTrue(is_array($search));
      $search = $this->model->find_first();
      $this->assertIsA($search, "WXActiveRecord");
      $search = $this->model->find_by_sql("SELECT * FROM test_model WHERE username='test2'");
      $this->assertTrue(is_array($search));
      $this->assertEqual(count($search), 1);
    }
    
    public function test_find_array() {
      $result = $this->model->find(array(1,2,3));
      $this->assertEqual(count($result), 3);
    }
    
    public function test_simple_dynamic_finders() {
      $search = $this->model->find_by_username("test1");
      $this->assertIsA($search, "WXActiveRecord");
      $search = $this->model->find_all_by_username("test1");
      $this->assertTrue(is_array($search));
      $this->assertTrue(count($search)==2);
    }
    
    public function test_complex_dynamic_finders() {
      $search = $this->model->find_by_username_and_password("test1", "password");
      $this->assertIsA($search, "WXActiveRecord");
      $search = $this->model->find_all_by_username_and_password("test1", "password");
      $this->assertTrue(is_array($search));
      $this->assertTrue(count($search)==2);
    }
    
    public function test_attributes() {
      $this->model1->username="changed";
      $this->assertEqual("changed", $this->model1->username);
    }
    
    public function test_constraints() {
      $this->model->setConstraint("username", "test1");
      $search = $this->model->find_all();
      $this->assertEqual(count($search), 2);
    }
    
    public function test_parameter_processing() {
      $search = $this->model->find_all(array("conditions"=>"username='test2'"));
      $this->assertTrue(is_array($search));
      $this->assertEqual(count($search), 1);
      $search = $this->model->find_all(array("order"=>"email DESC"));
      $first = $search[0];
      $this->assertEqual($first->email, "test3@test.com");
      $search = $this->model->find_all(array("limit"=>"1"));
      $this->assertTrue(is_array($search));
      $this->assertEqual(count($search), 1);
      $search = $this->model->find_all(array("limit"=>"1", "offset"=>"1"));
      $first = $search[0];      
      $this->assertEqual($first->email, "test2@test.com");
    }
    
    public function test_associations() {
      $result = $this->model1->test_model;
      $this->assertIsA($result, "WXActiveRecord");
    }
    
    public function test_exception_handling() {
      try {
        $result = $this->model1->test_model2;
      } catch(Exception $e) {
        $this->assertIsA($e, "PDOException");
      }
    }
    
    public function test_insert() {
      $model = new TestModel;
      $this->assertNotNull($model->update_attributes($this->get_fixture("user3")));
    }
    
    public function test_update() {
      $model = $this->model->find(1);
      $model->username="changed";
      $this->assertNotNull($model->save() );
      $this->assertEqual($model->username, "changed");
    }
    
    public function test_delete() {
      $id = $this->model3->id;
      $this->assertNotNull($this->model->delete($id) );
      $this->assertNull($this->model->find($id));
    }

		public function test_has_many() {
			$this->assertTrue(array_key_exists("images", $this->model1->has_many_throughs));
			$this->assertEqual($this->model1->has_many_throughs['images'][0], "test_model2");
			$this->assertEqual($this->model1->has_many_throughs['images'][1], "test_model_test_model2");
			$this->model1->images = "4";
			$this->model1->images = "12";
			$this->assertEqual(count($this->model1->images), 2);
		}
		
		public function test_has_many_methods() {
			$this->model1->images = "4";
			$this->model1->images = "12";
			$this->model1->delete_images("12");
			$this->assertEqual(count($this->model1->images), 1);
			$this->model1->clear_images();
			$this->assertEqual(count($this->model1->images), 0);
			$this->model1->images = "4";
			$this->model1->images = "4";
			$this->model1->images = "4";
			$this->model1->images = "4";
			$this->assertEqual(count($this->model1->images), 1);
		}
    
}

?>