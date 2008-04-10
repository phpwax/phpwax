<?php

class PaginatedModel extends WaxModel {
  public function setup() {
    $this->define("name", "CharField", array("maxlength"=>40));
  }
}



class TestPaginatedRecordset extends WXTestCase {
    public function setUp() {
      $this->model = new PaginatedModel;
      $this->model->syncdb();
      foreach($this->gen_fixtures("name", "user", 45) as $row) {
        $this->model->create($row);
      }
    }
    
    public function tearDown() {
      $this->model->clear()->delete();
    }
    
    public function gen_fixtures($field, $value, $number) {
      for($i=1; $i <=$number; $i++) {
        $fixtures[]= array($field => $value.$number);
      }
      return $fixtures;
    }
    
    public function test_simple_page() {
      $this->assertEqual($this->model->all()->count(), 40);
    }
    
    
}







