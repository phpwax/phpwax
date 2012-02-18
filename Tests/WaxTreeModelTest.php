<?php
namespace Wax\Tests;
use Wax\Model\Tree;
use Wax\Model\Model;


class TreeExample extends Tree {
  public $cache_tree = FALSE;
  
  public function setup() {
    $this->define("section_name", "CharField", array("maxlength" => 40));
  }
}

class WaxTreeModelTest extends WaxTestCase {
    public function setUp() {
      $this->model = new TreeExample;
      $this->model->syncdb();
    }
    
    public function tearDown() {
      Model::$db->drop_table($this->model->table);
    }
    
    public function test_parent() {
      $res1 = $this->model->create(array("section_name" => "section1"));
      $res2 = $this->model->create(array("section_name" => "section1-2"));
      $res3 = $this->model->create(array("section_name" => "section1-2-3"));

      $res3->parent = $res2;
      $res2->parent = $res1;
      
      $res4 = $this->model->filter(array("section_name" => "section1-2-3"))->first();
      
      $this->assertEquals($res4->parent->parent->section_name, "section1");
    }
    
    public function test_children() {
      $res1 = $this->model->create(array("section_name" => "section1"));
      $res2 = $this->model->create(array("section_name" => "section1-1"));
      $res3 = $this->model->create(array("section_name" => "section1-2"));

      $res3->parent = $res1;
      $res2->parent = $res1;
      
      $res4 = $this->model->filter(array("section_name" => "section1"))->first();
      
      $this->assertEquals($res4->children->count(), 2);
    }
    
    public function test_get_level() {
      $res1 = $this->model->create(array("section_name" => "section1"));
      $res2 = $this->model->create(array("section_name" => "section1-2"));
      $res3 = $this->model->create(array("section_name" => "section1-2-3"));

      $res3->parent = $res2;
      $res2->parent = $res1;
      
      $res4 = $this->model->filter(array("section_name" => "section1-2-3"))->first();
      
      $this->assertEquals($res4->get_level(), 2);
      $this->assertEquals($res4->parent->get_level(), 1);
      $this->assertEquals($res4->parent->parent->get_level(), 0);
      $this->assertEquals($res4->root()->get_level(), 0);
    }
}







