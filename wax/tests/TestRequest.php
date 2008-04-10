<?php

class TestRequest extends WXTestCase {
    public function setUp() {
      $_POST= array();
      $_GET = array();
      WaxUrl::$params=false;
      Request::$params=false;
    }
    
    public function tearDown() {
      print_r(Request::$params);
    }
    
    public function test_basic_get_post() {
      $_GET["test"]="hello";
      $_POST["test2"]="hello";
      $this->assertEqual(Request::get("test"), "hello");
      $this->assertEqual(Request::get("test2"), "hello");
    }
    
    public function test_filters() {
      $_GET["test"]="<script>hello</script>";
      $_GET["test2"]="<p>hello</p>";
      $_GET["test3"]="'hello'";
      $this->assertEqual(Request::get("test"), "<script>hello</script>");
      $this->assertEqual(Request::get("test2"), "<p>hello</p>");
      $this->assertEqual(Request::get("test3"), "'hello'");
    }
   
}







