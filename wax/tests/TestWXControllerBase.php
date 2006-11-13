<?php

class TestController extends WXControllerBase {}

class TestWXControllerBase extends WXTestCase 
{
    public function setUp() {
      $this->cont = new TestController();
    }
    
    public function tearDown() {}
    
    public function 
    
    public function test_redirect_to() {
      $this->cont->redirect_to("test");
    }
}

?>