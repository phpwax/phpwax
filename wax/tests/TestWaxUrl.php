<?php

class TestWaxUrl extends WXTestCase {
    public function setUp() {
      $_GET["route"]="mycontroller/myaction/myid";
    }
    
    public function tearDown() {

    }
    
    public function test_map() {
      WaxUrl::perform_mappings();
      $this->assertEqual(WaxUrl::get("controller"), "mycontroller");

    }
    
}







