<?php

class TestRequest extends WXTestCase {
    public function setUp() {
      WaxUrl::$params=false;
    }
    
    public function tearDown() {
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
      $this->assertEqual(Request::get("test"), "hello");
      $this->assertEqual(Request::get("test2"), "hello");
    }
   
}







