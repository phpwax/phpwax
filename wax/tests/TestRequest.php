<?php

class TestRequest extends WXTestCase {
    public function setUp() {

    }
    
    public function tearDown() {
    }
    
    public function test_basic_get() {
      $_GET["test"]="hello";
      $this->assertEqual(Request::get("test"), "hello");
    }
   
}







