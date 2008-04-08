<?php

class TestWaxUrl extends WXTestCase {
    public function setUp() {
      $_GET = false;
    }
    
    public function tearDown() {
     
    }
    
    public function test_basic_map() {
      $_GET["route"]="mycontroller/myaction/myid";
      $this->assertEqual(WaxUrl::get("controller"), "mycontroller");
<<<<<<< HEAD:wax/tests/TestWaxUrl.php
<<<<<<< HEAD:wax/tests/TestWaxUrl.php
=======
=======
>>>>>>> 998c8595b5f2b41a8784197b3f8270c0d620fd2d:wax/tests/TestWaxUrl.php
      $this->assertEqual(WaxUrl::get("action"), "myaction");
      $this->assertEqual(WaxUrl::get("id"), "myid");
    }
    
    public function test_partial_basic() {
      $_GET["route"]="mycontroller/myaction";
      $this->assertEqual(WaxUrl::get("controller"), "mycontroller");
      $this->assertEqual(WaxUrl::get("action"), "myaction");
      $_GET["route"]="mycontroller";
      $this->assertEqual(WaxUrl::get("controller"), "mycontroller");
<<<<<<< HEAD:wax/tests/TestWaxUrl.php
>>>>>>> 998c8595b5f2b41a8784197b3f8270c0d620fd2d:wax/tests/TestWaxUrl.php
=======
>>>>>>> 998c8595b5f2b41a8784197b3f8270c0d620fd2d:wax/tests/TestWaxUrl.php
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
    }
    
    public function test_wildcard_map() {
      $_GET["route"]="article/tech/humour/pics";
      WaxUrl::map("article/:tags*", array("controller"=>"blog", "action"=>"tags"));
      $this->assertTrue(is_array(WaxUrl::get("tags")) );
      $this->assertEqual(count(WaxUrl::get("tags")), 3);
    }
    
    public function test_formats() {
      $_GET["route"]="sitemap.xml";
    }
    
   
}







