<?php
class ExampleFile extends WaxModel {
  
  public function setup() {
    $this->define("filename", "FileField", array("maxlength"=>255));
  }
}
class ExampleFileField extends WaxModel {
  
  public function setup() {
		//restrict file uploads to just gifs and jpgs
    $this->define("file", "FileField", array("maxlength"=>255, 'allowed_extensions'=> array('.gif', '.jpg') ));
  }
}
class TestWaxModelField extends WXTestCase {
    public function setUp() {
      $this->model = new Example();
      $this->model_owner = new ExampleOwner();
      $this->model_editor = new ExampleEditor();
      $this->model_file = new ExampleFile();
			$this->model_file_field = new ExampleFileField();
      $this->model->syncdb();
      $this->model_owner->syncdb();
      $this->model_editor->syncdb();
      $this->model_file->syncdb();
      $this->model_file_field->syncdb();
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
    
    public function test_validate_unique() {
      $this->model->define("username", "CharField", array("unique" => true));
      $model1 = $this->model->create($this->get_fixture("user1"));
      $model2 = $this->model->set_attributes($this->get_fixture("user1"));
      $this->assertFalse($model2->validate());
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
    
    public function test_has_many_without_foreign_key_definition() {
      $editor = $this->model_editor->create(array("name"=>"Editor"));
      $model = $this->model->create($this->get_fixture("user1"));
      $model2 = $this->model->create($this->get_fixture("user2"));
      $editor->examples = $model;
      $editor->examples = $model2;
      $this->assertEqual($editor->examples->count(), 2);
      $this->assertEqual($editor->examples->filter(array("email" => "test1@test.com"))->all()->count(), 1);
      $model3 = $this->model->create($this->get_fixture("user3"));
      $this->assertEqual($editor->examples->filter(array("email" => "test3@test.com"))->all()->count(), 0);
      $editor->examples->unlink($this->model->all());
      $this->assertEqual($editor->examples->count(), 0);
      $editor->examples = $model3;
      $this->assertEqual($editor->examples->count(), 1);
    }
    
    public function test_many_many() {
      $model = $this->model->create($this->get_fixture("user1"));
      $props = new ExampleProperty;
      $prop1 = $props->create(array("name"=>"Property 1"));
      $prop2 = $props->create(array("name"=>"Property 2"));
      $model->properties = $props->all();
      $test = $model->properties;
      $this->assertIsA($model->properties, "WaxModelAssociation");
      $this->assertIsA($model->properties[0], "ExampleProperty");
      $this->assertIsA($model->properties[0]->examples[0], "Example");
      $this->assertEqual($model->properties->count(), 2);
      $this->assertEqual($model->properties->filter(array("name" => "Property 1"))->all()->count(), 1);
      $prop3 = $props->create(array("name"=>"Property 3"));
      $this->assertEqual($model->properties->filter(array("name" => "Property 3"))->all()->count(), 0);
      $model->properties->unlink($prop1);
      $this->assertEqual($model->properties->count(), 1);
      $model->properties = $prop1;
      $this->assertEqual($model->properties->count(), 2);
      $model->properties->unlink($props->all());
      $this->assertEqual($model->properties->count(), 0);
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
	    $this->file_upload_prep();
			$this->model_file->save();
	   	$first_name = $this->model_file->filename;
			$model = new ExampleFile;
	    $this->file_upload_prep();
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
			$test_dir = WAX_ROOT.$model->file->file_root;
		  file_put_contents(PUBLIC_DIR."testfile.txt", "test file");
	    $_FILES[$model->table]['name'][$column] = "testfile.txt";
			$_FILES[$model->table]['size'][$column] = filesize(PUBLIC_DIR."testfile.txt");
			$_FILES[$model->table]['tmp_name'][$column] = PUBLIC_DIR."testfile.txt";
		}
    
}







