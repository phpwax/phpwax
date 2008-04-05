<?php

class TestWaxUrl extends WXTestCase {
    public function setUp() {
      $_GET = false;
    }
    
    public function tearDown() {
      print_r($_GET);
      echo "\n"."-------------"."\n";
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
    
    public function test_pattern_map() {
      $_GET["route"]="blog/tech/5";
      WaxUrl::map("blog/:category/:id", array("controller"=>"blog"));
      WaxUrl::perform_mappings();
      $this->assertEqual(WaxUrl::get("controller"), "blog");
      $this->assertEqual(WaxUrl::get("category"), "tech");
    }
    
   
}







