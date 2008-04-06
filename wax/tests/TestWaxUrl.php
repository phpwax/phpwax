<?php

class TestWaxUrl extends WXTestCase {
    public function setUp() {
      $_GET = false;
    }
    
    public function tearDown() {
      print_r($_GET);
      echo "\n"."-------------"."\n";
    }
    
    // public function test_basic_map() {
    //   $_GET["route"]="mycontroller/myaction/myid";
    //   WaxUrl::perform_mappings();
    //   $this->assertEqual(WaxUrl::get("controller"), "mycontroller");
    //   $this->assertEqual(WaxUrl::get("action"), "myaction");
    //   $this->assertEqual(WaxUrl::get("id"), "myid");
    // }
    // 
    // public function test_default_map() {
    //   $_GET["route"]="";
    //   WaxUrl::perform_mappings();
    //   $this->assertEqual(WaxUrl::get("controller"), "page");
    // }
    // 
    // public function test_pattern_map() {
    //   $_GET["route"]="blog/tech/5";
    //   WaxUrl::map("blog/:category/:id", array("controller"=>"blog", "action"=>"show"));
    //   WaxUrl::perform_mappings();
    //   $this->assertEqual(WaxUrl::get("controller"), "blog");
    //   $this->assertEqual(WaxUrl::get("category"), "tech");
    // }
    
    public function test_wildcard_map() {
      $_GET["route"]="tags/tech/humour/pics";
      WaxUrl::map("article/:tags*", array("controller"=>"blog", "action"=>"tags"));
      WaxUrl::perform_mappings();
      $this->assertTrue(is_array(WaxUrl::get("tags")) );
      $this->assertEqual(count(WaxUrl::get("tags")), 3);
    }
    
   
}







