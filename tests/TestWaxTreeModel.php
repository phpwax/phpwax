<?php
class TreeExample extends WaxTreeModel {
  public function setup() {
    $this->define("section_name", "CharField", array("maxlength" => 40));
  }
}

class TestWaxTreeModel extends WXTestCase {
    public function setUp() {
      $this->model = new TreeExample;
      $this->model->syncdb();
    }
    
    public function tearDown() {
      WaxModel::$db->drop_table($this->model->table);
    }
    
    public function test_parent() {
      $res1 = $this->model->create(array("section_name" => "section1"));
      $res2 = $this->model->create(array("section_name" => "section1-2"));
      $res3 = $this->model->create(array("section_name" => "section1-2-3"));

      $res3->parent = $res2;
      $res2->parent = $res1;
      
      $res4 = $this->model->filter(array("section_name" => "section1-2-3"))->first();
      
      $this->assertEqual($res4->parent->parent->section_name, "section1");
    }
    
    public function test_children() {
      $res1 = $this->model->create(array("section_name" => "section1"));
      $res2 = $this->model->create(array("section_name" => "section1-1"));
      $res3 = $this->model->create(array("section_name" => "section1-2"));

      $res3->parent = $res1;
      $res2->parent = $res1;
      
      $res4 = $this->model->filter(array("section_name" => "section1"))->first();
      
      $this->assertEqual($res4->children->count(), 2);
    }
    
    public function test_get_level() {
      $res1 = $this->model->create(array("section_name" => "section1"));
      $res2 = $this->model->create(array("section_name" => "section1-2"));
      $res3 = $this->model->create(array("section_name" => "section1-2-3"));

      $res3->parent = $res2;
      $res2->parent = $res1;
      
      $res4 = $this->model->filter(array("section_name" => "section1-2-3"))->first();
      
      $this->assertEqual($res4->get_level(), 2);
      $this->assertEqual($res4->parent->get_level(), 1);
      $this->assertEqual($res4->parent->parent->get_level(), 0);
      $this->assertEqual($res4->roots[0]->get_level(), 0);
    }
}







