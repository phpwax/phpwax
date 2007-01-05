<?php

class TestWXConfiguration extends WXTestCase 
{
    public function setUp() {
      $this->config = new WXConfiguration;
    }
    
    public function tearDown() {
      
    }

  	public function test_replace_yaml() {
  	  
  	}

  	public function test_set() {
  	  
  	}

    public function test_get($value) { 
  	  $config = WXConfiguration::get('db');
  	  $this->assertTrue(is_array($config));
  	}

  	public function test_set_environment($env) {
  	  
  	}

    
}
?>