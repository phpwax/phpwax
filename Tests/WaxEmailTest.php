<?php
namespace Wax\Tests;
use Wax\Utilities\Email;


class TestEmail extends Email {
  public function counter($variable) {
    return count($this->$variable);
  }
  
  public function get($variable) {
    return $this->$variable;
  }
}

class WaxEmailTest extends WaxTestCase {
  
  public function setUp() {
    $this->email = new TestEmail;
  }
  
  public function tearDown() {}
  
  public function test_add_to_address() {
    $this->email->add_to_address("test@test.com", "Test User");
    $this->assertEquals(count($this->email->counter("to")), "1");
  }

 
  public function test_add_cc_address() {
    $this->email->add_cc_address("test@test.com", "Test User");
    $this->assertEquals(count($this->email->counter("cc")), "1");
  }

  public function test_add_bcc_address() {
    $this->email->add_bcc_address("test@test.com", "Test User");
    $this->assertEquals(count($this->email->counter("bcc")), "1");
  }

  public function test_add_replyto_address() {
    $this->email->add_replyto_address("test@test.com", "Test User");
    $this->assertEquals(count($this->email->counter("replyto")), "1");
  }
  
  public function test_is_html() {
    $this->assertEquals($this->email->ContentType, "text/plain");
    $this->email->is_html(true);
    $this->assertEquals($this->email->ContentType, "text/html");
    $this->email->is_html(false);
    $this->assertEquals($this->email->ContentType, "text/plain");
  }
  
}
?>