<?php

class TestRequest extends WXTestCase {
    public function setUp() {
      $_GET = array();
    }
    
    public function tearDown() {}
    
    public function test_basic_get() {
      $_GET["test"]="<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>";
      $this->assertNotEqual(Request::get("test"), Request::raw("test"));
    }
   
}







