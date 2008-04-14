<?php

class TestConfig extends WXTestCase 
{
    public function setUp() {
      Config::set_environment('test');
    }
    
    public function tearDown() {}

  	public function test_set_array() {
  	  Config::set(array("newtest"=>"5"));
  	  $this->assertEqual(Config::get('newtest'), 5);
  	}
  	
  	public function test_set() {
  	  Config::set("myconf", "myval");
  	  $this->assertEqual(Config::get('myconf'), "myval");
  	}

    public function test_get() { 
  	  $config = Config::get('all');
  	  $this->assertTrue(is_array($config));
  	}
  	
  	public function test_return_false() { 
  	  $config = Config::get('rubbish');
  	  $this->assertFalse($config);
  	}

  	public function test_set_environment($env) {
  	  Config::set_environment('test');
  	  $this->assertEqual(Config::get('test/db'), Config::get('db'));
  	}

    
}
?>