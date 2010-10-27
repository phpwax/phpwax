<?php

class TestController extends WaxController {}

class TestWaxController extends WXTestCase 
{
    public function setUp() {
      $this->cont = new TestController();
    }
    
    public function tearDown() {}
        
    public function test_add_filters() {
      $this->cont->before_filter("index", "test");
      $this->assertTrue(array_key_exists("before", $this->cont->filters));
      $this->assertTrue($this->cont->filters["before"]['index']=="test");
      $this->cont->after_filter("index", "test");
      $this->assertTrue(array_key_exists("after", $this->cont->filters));
      $this->assertTrue($this->cont->filters["after"]['index']=="test");
      $this->cont->before_filter("all", "test", array("index"));
    }
    
    public function test_run_filters() {
      Mock::generatePartial('TestController', "MockTestController", array("test", "index", "test2"));
      $controller = new MockTestController();
      $controller->before_filter("index", "test");
      $controller->before_filter("all", "test");
      $controller->after_filter("index", "test");
      $controller->after_filter("all", "test");
      $controller->action="index";
      $controller->run_filters('before');
      $controller->run_filters('after');
      $controller->expectCallCount("test", 4);
      $controller->filters["before"]=array();
      $controller->before_filter("all", "test2", array("index"));
      $controller->action="index";
      $controller->action="test";
      $controller->run_filters('before');
      $controller->expectCallCount("test2", 1);
      
    }
}

?>