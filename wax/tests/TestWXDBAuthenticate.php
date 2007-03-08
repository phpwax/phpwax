<?php
class TestAuthUser extends WXActiveRecord {}

class TestAuthMigration extends WXMigrate {
  public function up() {
    $this->create_column("username");
    $this->create_column("password");
    $this->create_column("email");
    $this->create_table("test_auth_user");
  }
  
  public function down() {
    $this->drop_table("test_auth_user");
  }
}

class TestWXDBAuthenticate extends WXTestCase 
{
  public function setUp() {
    $migrate = new TestAuthMigration('quiet');
    $migrate->up();
    $this->model = new TestAuthUser();
    $this->model->update_attributes($this->get_fixture("user"));
  }
  
  public function tearDown() {
    $migrate = new TestAuthMigration('quiet');
    $migrate->down();
  }
  
  protected function get_fixture($type) {
    $fixtures = array(
      "user" => array("username"=>"test1", "password"=>"password", "email"=>"test1@test.com"),
      "encrypteduser" => array("username"=>"encrypted", "password"=>md5("password"), "email"=>"test2@test.com"),
    );
    return $fixtures[$type];
  }
  
  public function test_verify() {
    $auth = new WXDBAuthenticate(array("encrypt"=>false, "db_table"=>"test_auth_user"));
    $auth->verify("test1", "password");
    $this->assertTrue($auth->is_logged_in());
    $auth->logout();
    $this->assertFalse($auth->is_logged_in());
  }
  
  public function test_bad_login_fails() {
    $auth = new WXDBAuthenticate(array("encrypt"=>false, "db_table"=>"test_auth_user"));
    $this->assertFalse($auth->is_logged_in());
    $auth->verify("test1", "badpassword");
    $this->assertFalse($auth->is_logged_in());
  }
  
  public function test_encrypted_login() {
    $this->model->update_attributes($this->get_fixture("encrypteduser"));
    $auth = new WXDBAuthenticate(array("encrypt"=>true, "db_table"=>"test_auth_user"));
    $auth->verify("encrypted", "password");
    $this->assertTrue($auth->is_logged_in());
  }
  
}





?>


