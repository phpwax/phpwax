<?php

class TestTag extends WXTreeRecord {}
class TestTagMigration extends WXMigrate {
  public function up() {
    $this->create_column("name");
    $this->create_column("parent_id");
    $this->create_table("test_tag");
  }
  
  public function down() {
    $this->drop_table("test_tag");
  }
}

class TestWXTreeRecord extends WXTestCase {
  
  public function setUp() {
    $migrate = new TestTagMigration;
    $migrate->up();
    $this->model = new TestTag();
    $this->model1 = new TestTag();
    $this->model->update_attributes($this->get_fixture("tag1"));
    $this->model1->update_attributes($this->get_fixture("tag2"));
    $this->child1 = $this->model->create_child(array("name"=>"Child of Parent 1"));
  }
  
  public function tearDown() {
    $migrate = new TestTagMigration;
    $migrate->down();
  }
  
  public function get_fixture($type) {
    $fixtures = array(
      "tag1" => array("name"=>"Parent 1", "parent_id"=>"0"),
      "tag2" => array("name"=>"Parent 2", "parent_id"=>"0")
    );
    return $fixtures[$type];
  }
  
  public function test_has_children() {
    $this->assertNotNull($this->model->has_children());
  }
  
  public function test_create_child() {
    $sibling = $this->model->create_child(array("name"=>"Child a of Parent 1"));
    $this->assertEqual($this->model->id, $sibling->parent_id);
    $this->model->delete($sibling->id);
  }
  
  public function test_siblings() {
    $sibling = $this->model->create_child(array("name"=>"Child a of Parent 1"));
    $this->model->create_child(array("name"=>"Child b of Parent 1"));
    $this->assertEqual(count($sibling->siblings()), 3);
  }
  
  public function test_is_root() {
    $this->assertTrue($this->model->is_root());
    $this->assertFalse($this->child1->is_root());
  }

  public function test_get_level() {
    $this->assertEqual($this->model->get_level(), 0);
    $this->assertEqual($this->child1->get_level(), 1);
    
  }
  
  
}





?>