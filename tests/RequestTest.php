<?php
namespace Wax\Tests;


class RequestTest extends WaxTestCase {
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
      $this->assertEquals(Request::get("test"), "hello");
      $this->assertEquals(Request::post("test2"), "hello");
      $this->assertEquals(Request::param("test"), "hello");
      $this->assertEquals(Request::param("test2"), "hello");
    }
   
}







