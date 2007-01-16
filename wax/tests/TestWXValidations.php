<?php

Mock::generatePartial("WXValidations", "MockWXValidations", array());

class TestWXValidations extends WXTestCase 
{
  public function setUp() {
    $this->object = new MockWXValidations();
  }
  
  public function tearDown() {
    
  }
  
  public function test_valid_format() {
    $this->object->email = "badaddressbad.bad.com";
    $this->object->valid_format("email", "email");
    $this->assertFalse($this->object->validate());
    $this->object->clear_errors();
    $this->object->email = "goodaddress@good.com";
    $this->object->valid_format("email", "email");
    $this->assertTrue($this->object->validate());
  }
  
  public function test_custom_valid_format() {
    $this->object->number = "01234ABC";
    $this->object->valid_format("number", "/^[0-9]*$/");
    $this->assertFalse($this->object->validate());
    $this->object->clear_errors();
    $this->object->number = "0123445432";
    $this->object->valid_format("number", "/^[0-9]*$/");
    $this->assertTrue($this->object->validate());
  }
  
  public function test_valid_required() {
    $this->object->name = "";
    $this->object->valid_required("name");
    $this->assertFalse($this->object->validate());
    $this->object->clear_errors();
    $this->object->name = "a name";
    $this->object->valid_required("name");
    $this->assertTrue($this->object->validate());
  }
  
  public function test_valid_length() {
    $this->object->name = "short";
    $this->object->valid_length("name", 6);
    $this->assertFalse($this->object->validate());
    $this->object->clear_errors();
    $this->object->name = "longer";
    $this->object->valid_length("name", 6);
    $this->assertTrue($this->object->validate());
    $this->object->clear_errors();
    $this->object->name = "longer";
    $this->object->valid_length("name", 0,4);
    $this->assertFalse($this->object->validate());
    $this->object->clear_errors();
    $this->object->name = "longer";
    $this->object->valid_length("name", 0,7);
    $this->assertTrue($this->object->validate());
  }
  
  
}

?>