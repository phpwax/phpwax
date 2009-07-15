<?php
class TestAuthUser extends WaxModel {
  public function setup() {
    $this->define("username", "CharField");
    $this->define("password", "CharField");
    $this->define("email", "EmailField");
  }
}


class TestWaxAuthDb extends WXTestCase {
  
  public function setUp() {
    $this->model = new TestAuthUser();
    $this->model->syncdb();
    $this->model->update_attributes($this->get_fixture("user"));
  }
  
  public function tearDown() {
    WaxModel::$db->drop_table($this->model->table);
  }
  
  protected function get_fixture($type) {
    $fixtures = array(
      "user" => array("username"=>"test1", "password"=>"password", "email"=>"test1@test.com"),
      "encrypteduser" => array("username"=>"encrypted", "password"=>md5("password"), "email"=>"test2@test.com"),
    );
    return $fixtures[$type];
  }
  
  public function test_verify() {
    $auth = new WaxAuthDb(array("encrypt"=>false, "db_table"=>"test_auth_user"));
    $auth->verify("test1", "password");
    $this->assertTrue($auth->is_logged_in());
    $auth->logout();
    $this->assertFalse($auth->is_logged_in());
  }
  
  public function test_bad_login_fails() {
    $auth = new WaxAuthDb(array("encrypt"=>false, "db_table"=>"test_auth_user"));
    $this->assertFalse($auth->is_logged_in());
    $auth->verify("test1", "badpassword");
    $this->assertFalse($auth->is_logged_in());
  }
  
  public function test_encrypted_login() {
    $this->model->update_attributes($this->get_fixture("encrypteduser"));
    $auth = new WaxAuthDb(array("encrypt"=>true, "db_table"=>"test_auth_user"));
    $auth->verify("encrypted", "password");
    $this->assertTrue($auth->is_logged_in());
  }
  
}



