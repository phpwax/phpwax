<?php
namespace Wax\Tests;
use Wax\Model\Model;



class ExampleFile extends Model {
  
  public function setup() {
    $this->define("filename", "FileField");
  }
}
class ExampleUnique extends Model {
  public function setup() {
    $this->define("username", "CharField", array("maxlength"=>40, "unique" => true));
    $this->define("password", "CharField", array("blank"=>false, "maxlength"=>15));
    $this->define("email", "CharField", array("maxlength"=>255));
  }
}
class ExampleFileField extends Model {
  
  public function setup() {
		//restrict file uploads to just gifs and jpgs
    $this->define("file", "FileField", array('allowed_extensions'=> array('.gif', '.jpg') ));
  }
}
class WaxModelFieldTest extends WaxTestCase {
    public function setUp() {
      $this->model = new Example();
      $this->model_owner = new ExampleOwner();
      $this->model_editor = new ExampleEditor();
      $this->model_file = new ExampleFile();
			$this->model_file_field = new ExampleFileField();
      $this->model_property = new ExampleProperty();
      $this->model_one_way_property = new ExampleOneWayProperty();
      $this->model->syncdb();
      $this->model_owner->syncdb();
      $this->model_editor->syncdb();
      $this->model_file->syncdb();
      $this->model_file_field->syncdb();
      $this->model_property->syncdb();
      $this->model_one_way_property->syncdb();
    }
    
    public function tearDown() {
      Model::$db->drop_table($this->model->table);
      Model::$db->drop_table($this->model_owner->table);
      Model::$db->drop_table($this->model_editor->table);
      Model::$db->drop_table($this->model_file->table);
      Model::$db->drop_table($this->model_file_field->table);
      Model::$db->drop_table($this->model_property->table);
      Model::$db->drop_table($this->model_one_way_property->table);
      Model::$db->drop_table("example_example_property");
      Model::$db->drop_table("example_example_one_way_property");
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
      $this->assertEquals($res->username, "test1");
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
    }
    
    public function test_validate_unique() {
			$model1 = new ExampleUnique();
			$model1->syncdb();
      $model1->clear()->query("delete from ".$model1->table);
			//check that duplicates are found and not saved
      $model1->set_attributes($this->get_fixture("user1"));
			$model1 = $model1->save();
			$this->assertFalse(count($model1->errors));
			$model2 = new ExampleUnique();
      $model2->set_attributes($this->get_fixture("user1"));
			$model2 = $model2->save();
			$this->assertFalse(count($model2->errors));
			//check that you can change the unique value of a model
      $model1->set_attributes($this->get_fixture("user2"));
			$model1 = $model1->save();
			$this->assertFalse(count($model2->errors));
			//cleanup after the syncdb
			Model::$db->drop_table($model1->table);
    }
    
    public function test_foreign_key() {
      $owner = $this->model_owner->create(array("name"=>"Master"));
      $model = $this->model->create($this->get_fixture("user1"));
      $model->example_owner = $owner;
      $this->assertEquals("test1", $model->username);
      $this->assertEquals("Master", $model->example_owner->name);
    }
    
    public function test_has_many() {
      $owner = $this->model_owner->create(array("name"=>"Master"));
      $model = $this->model->create($this->get_fixture("user1"));
      $model2 = $this->model->create($this->get_fixture("user2"));
      $model->example_owner = $owner;
      $model2->example_owner = $owner;
      $this->assertEquals($owner->examples->count(), 2);
    }
    
    public function test_has_many_without_foreign_key_definition() {
      $editor = $this->model_editor->create(array("name"=>"Editor"));
      $model = $this->model->create($this->get_fixture("user1"));
      $model2 = $this->model->create($this->get_fixture("user2"));
      $editor->examples = $model;
      $editor->examples = $model2;
      $this->assertEquals($editor->examples->count(), 2);
      $this->assertEquals($editor->examples->filter(array("email" => "test1@test.com"))->all()->count(), 1);
      $model3 = $this->model->create($this->get_fixture("user3"));
      $this->assertEquals($editor->examples->filter(array("email" => "test3@test.com"))->all()->count(), 0);
      $editor->examples->unlink($this->model->all());
      $this->assertEquals($editor->examples->count(), 0);
      $editor->examples = $model3;
      $this->assertEquals($editor->examples->count(), 1);
    }
    
    public function test_has_many_with_filters(){
      $owner = $this->model_owner->create(array("name"=>"Master"));
      $model = $this->model->create($this->get_fixture("user1"));
      $model2 = $this->model->create($this->get_fixture("user2"));
      $model->example_owner = $owner;
      $model2->example_owner = $owner;
      $this->assertEquals($owner->examples->count(), 2);
      $this->assertEquals($owner->examples(array("email" => "test1@test.com"))->count(), 1);
    }
    
    public function test_many_many() {
      $model = $this->model->create($this->get_fixture("user1"));
      $props = new ExampleProperty;
      $prop1 = $props->create(array("name"=>"Property 1"));
      $prop2 = $props->create(array("name"=>"Property 2"));
      $model->propertiesLazy = $prop1;
      $model->propertiesEager = $prop2;

      $this->assertInstanceOf("WaxModelAssociation",$model->propertiesLazy);
      $this->assertInstanceOf("ExampleProperty", $model->propertiesLazy[0]);
      $this->assertInstanceOf("Example",$model->propertiesLazy[0]->examples[0]);
      
      $this->assertInstanceOf("WaxModelAssociation",$model->propertiesEager);
      $this->assertInstanceOf("ExampleProperty",$model->propertiesEager[0]);
      $this->assertInstanceOf("Example",$model->propertiesEager[0]->examples[0]);
      
      $this->assertInstanceOf("ExampleProperty",$model->propertiesLazy->filter("name", "Property 1")->first());
      $test_first = $model->propertiesLazy->filter("name", "Property 1")->all();
      $this->assertInstanceOf("ExampleProperty",$test_first[0]);      
      
      $this->assertInstanceOf("ExampleProperty",$model->propertiesEager->filter("name", "Property 2")->first());
      $test_first = $model->propertiesEager->filter("name", "Property 2")->all();
      $this->assertInstanceOf("ExampleProperty",$test_first[0]);

      $this->assertEquals($model->propertiesLazy->count(), 2);
      $this->assertEquals($model->propertiesEager->count(), 2);

      $this->assertEquals($model->propertiesEager(array("name"=>"Property 1"))->count(), 1);
      $this->assertEquals($model->propertiesLazy(array("name"=>"Property 1"))->count(), 1);

      $model->propertiesLazy->unlink($prop2);
      $this->assertEquals($model->propertiesLazy->count(), 1);
      $model->propertiesLazy = $prop2;
      $this->assertEquals($model->propertiesLazy->count(), 2);
      
      $model->propertiesEager->unlink($prop1);
      $this->assertEquals($model->propertiesEager->count(), 1);
      $model->propertiesEager = $prop1;
      $this->assertEquals($model->propertiesEager->count(), 2);
      
      $model->propertiesLazy->unlink($props->all());
      $this->assertEquals($model->propertiesLazy->count(), 0);
      $this->assertEquals($model->propertiesLazy->first(), false);

      $model->propertiesLazy = $prop1;
      $model->propertiesEager = $prop2;

      $model->propertiesEager->unlink($props->all());
      $this->assertEquals($model->propertiesEager->count(), 0);
      $this->assertEquals($model->propertiesEager->first(), false);

    }

    public function test_many_many_one_side() {
      $model = $this->model->create($this->get_fixture("user1"));
      $props = new ExampleOneWayProperty;
      $prop1 = $props->create(array("name"=>"Property 1"));
      $prop2 = $props->create(array("name"=>"Property 2"));
      $prop3 = $props->create(array("name"=>"Property 3"));
      $model->oneWayProperties = $prop1;
      $model->oneWayProperties = $prop2;
      $model->oneWayProperties = $prop3;

      $prop2->delete();
      $counter = 0;
      foreach($model->oneWayProperties as $property) $counter++;
      $this->assertEquals($counter, 2);
      $this->assertEquals($model->oneWayProperties->count(), 2);

      $rand_sql = new Model();
      $rand_sql->query('delete from example_one_way_property where name = "Property 3"');
      $counter = 0;
      foreach($model->oneWayProperties as $property) $counter++;
      $this->assertEquals($counter, 1);
      $this->assertEquals($model->oneWayProperties->count(), 1);
    }

		/*** FILE UPLOAD ****/

	  /* //HIDDEN DUE TO ITS DESTRUCTIVE NATURE - notice the system rm command!
		public function test_file_save_with_missing_file_dir() {  
			$test_dir = WAX_ROOT.$this->model_file->file->file_root;
			system("rm -Rf ". $test_dir);
			$this->file_upload_prep();
			$this->model_file->save();			
			$file = $test_dir ."testfile.txt";
			//test if directory was created
 	    $this->assertTrue(is_dir($test_dir));
			//test if file was moved
	   	$this->assertTrue(is_readable($file) );
			unlink(PUBLIC_DIR."testfile.txt");
			unlink($test_dir."testfile.txt");			
	  }
		*/
	  public function test_duplicate_file_renames() {
	    $this->file_upload_prep("filename", $this->model_file);
			$this->model_file->save();
	   	$first_name = $this->model_file->filename;
			$model = new ExampleFile;
	    $this->file_upload_prep("filename", $model);
			$model->save();
	   	$second_name = $model->filename;	
			if($second_name != $first_name) $this->assertTrue(true);
			else $this->assertFalse(false);
	  }
	
		public function test_excepted_files(){
			$file = new ExampleFileField();
			$this->file_upload_prep("file", $file);
			if($file = $file->save() ) $this->assertFalse(false);
			else $this->assertTrue(true);
		}

		protected function file_upload_prep($column="filename", $model){
			$test_dir = dirname(__FILE__);
		  file_put_contents($test_dir."/testfile.txt", "test file");
	    $_FILES[$model->table]['name'][$column] = "testfile.txt";
			$_FILES[$model->table]['size'][$column] = filesize($test_dir."/testfile.txt");
			$_FILES[$model->table]['tmp_name'][$column] = $test_dir."/testfile.txt";
		}
    
}







