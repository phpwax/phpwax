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
      die(print_r(get_class_methods('TestController')));
      Mock::generate('TestController');
      exit;
      $controller = new MockTestController();
      $controller->setReturnValue('before_filter', false);
      $res = $controller->before_filter("all", "test");
      $this->assertFalse($res);
    }
}

?>