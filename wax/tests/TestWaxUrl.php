<?php

class TestWaxUrl extends WXTestCase {
    public function setUp() {
      $_GET = false;
    }
    
    public function tearDown() {

    }
    
    public function test_basic_map() {
      $_GET["route"]="mycontroller/myaction/myid";
      WaxUrl::perform_mappings();
      $this->assertEqual(WaxUrl::get("controller"), "mycontroller");
    }
    
    public function test_default_map() {
      $_GET["route"]="";
      WaxUrl::perform_mappings();
      $this->assertEqual(WaxUrl::get("controller"), "page");
    }
    
}







