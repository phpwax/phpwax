<?php

class TestWaxUrl extends WXTestCase {
    public function setUp() {
    }
    
    public function tearDown() {

    }
    
    public function test_basic_map() {
      $_GET["route"]="mycontroller/myaction/myid";
      WaxUrl::perform_mappings();
      $this->assertEqual(WaxUrl::get("controller"), "mycontroller");
      print_r($_GET);
    }
    
    public function test_default_map() {
      $_GET["route"]="";
      WaxUrl::perform_mappings();
      $this->assertEqual(WaxUrl::get("controller"), "page");
      print_r($_GET);
    }
    
}







