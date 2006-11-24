<?php

class TestController extends WXControllerBase {}

class TestWXControllerBase extends WXTestCase 
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
      Mock::generatePartial('TestController', "MockTestController", array("test", "index"));
      $controller = new MockTestController();
      $controller->before_filter("index", "test");
      $controller->before_filter("all", "test");
      $controller->after_filter("index", "test");
      $controller->after_filter("all", "test");
      $controller->set_action("index");
      $controller->run_before_filters();
      $controller->run_after_filters();
      $controller->expectCallCount("test", 4);
    }
}

?>