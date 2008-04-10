<?php

class TestWaxUrl extends WXTestCase {
    public function setUp() {
      $_GET = false;
    }
    
    public function tearDown() {
     
    }
    
    public function test_basic_map() {
      $_GET["route"]="page/myaction/myid";
      $this->assertEqual(WaxUrl::get("controller"), "page");
      $this->assertEqual(WaxUrl::get("action"), "myaction");
      $this->assertEqual(WaxUrl::get("id"), "myid");
    }
    
    public function test_partial_basic() {
      $_GET["route"]="page/myaction";
      $this->assertEqual(WaxUrl::get("controller"), "page");
      $this->assertEqual(WaxUrl::get("action"), "myaction");
      $_GET["route"]="page";
      $this->assertEqual(WaxUrl::get("controller"), "page");
    }
    
    public function test_default_map() {
      $_GET["route"]="";
      $this->assertEqual(WaxUrl::get("controller"), "page");
    }
    
    public function test_pattern_map() {
      $_GET["route"]="blog/tech/5";
      WaxUrl::map("blog/:category/:id", array("controller"=>"blog", "action"=>"show"));
      $this->assertEqual(WaxUrl::get("controller"), "blog");
      $this->assertEqual(WaxUrl::get("category"), "tech");
      $this->assertEqual(WaxUrl::get("id"), "5");
      $_GET=array();
      $_GET["route"]="page/tech/5";
      WaxUrl::map("page/:category/:id", array("controller"=>"blog", "action"=>"page"));
      $this->assertEqual(WaxUrl::get("controller"), "blog");
      $this->assertEqual(WaxUrl::get("action"), "page");
      $this->assertEqual(WaxUrl::get("category"), "tech");
      $this->assertEqual(WaxUrl::get("id"), "5");
    }
    
    public function test_wildcard_map() {
      $_GET["route"]="article/tech/humour/pics";
      WaxUrl::map("article/:tags*", array("controller"=>"blog", "action"=>"tags"));
      $this->assertTrue(is_array(WaxUrl::get("tags")) );
      $this->assertEqual(count(WaxUrl::get("tags")), 3);
    }
    
    public function test_nested_controller() {
      $_GET["route"]="admin/content";
      $this->assertEqual(WaxUrl::get("controller"), "admin/content");      
    }
    
    public function test_formats() {
      $_GET["route"]="sitemap.xml";
      $this->assertEqual(WaxUrl::get("controller"), "page");
      $this->assertEqual(WaxUrl::get("action"), "sitemap.xml");    
    }
    
    public function test_defaults() {
      $_GET["route"]="contact";
      $this->assertEqual(WaxUrl::get("controller"), "page");
      $this->assertEqual(WaxUrl::get("action"), "contact");
      $_GET=array();
      $_GET["route"]="page";
      $this->assertEqual(WaxUrl::get("controller"), "page");
      $this->assertEqual(WaxUrl::get("action"), "index");
    }
    
    public function test_partial_default() {
      $_GET["route"]="gallery/anyid";
      $this->assertEqual(WaxUrl::get("controller"), "page");
      $this->assertEqual(WaxUrl::get("action"), "gallery");
      $this->assertEqual(WaxUrl::get("id"), "anyid");
    }
    
   
}







