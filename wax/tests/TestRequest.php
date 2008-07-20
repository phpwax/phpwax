<?php

class TestRequest extends WXTestCase {
    public function setUp() {
      $_POST= array();
      $_GET = array();
      WaxUrl::$params=false;
      Request::$params=false;
    }
    
    public function tearDown() {

    }
    
    public function test_basic_get_post() {
      $_GET["test"]="hello";
      $_POST["test2"]="hello";
      $this->assertEqual(Request::get("test"), "hello");
      $this->assertEqual(Request::post("test2"), "hello");
      $this->assertEqual(Request::param("test"), "hello");
      $this->assertEqual(Request::param("test2"), "hello");
    }
   
}







