<?php
namespace Wax\Tests;
use Wax\Model\Model;


class PaginatedModel extends Model {
  public function setup() {
    $this->define("name", "CharField", array("maxlength"=>40));
  }
}



class PaginatedRecordsetTest extends WaxTestCase {
    public function setUp() {
      $this->model = new PaginatedModel;
      $this->model->syncdb();
      foreach($this->gen_fixtures("name", "user", 45) as $row) {
        $this->model->create($row);
      }
    }
    
    public function tearDown() {
     WaxModel::$db->drop_table($this->model->table);
    }
    
    public function gen_fixtures($field, $value, $number) {
      for($i=1; $i <=$number; $i++) {
        $fixtures[]= array($field => $value.$i);
      }
      return $fixtures;
    }
    
    public function test_simple_page() {
      $this->assertEquals($this->model->all()->count(), 45);
      $this->assertEquals($this->model->page()->count(), 10);
      $this->assertEquals($this->model->page()->current_page, 1);
      $this->assertEquals($this->model->page("2")->first()->id, 11);
    }
    
    
}







