<?php
class TestUser extends WXActiveRecord {}

class TestAuthMigration extends WXMigrate {
  public function up() {
    $this->create_column("username");
    $this->create_column("password");
    $this->create_column("email");
    $this->create_table("test_user");
  }
  
  public function down() {
    $this->drop_table("test_user");
  }
}

class TestDBAuthorise extends WXTestCase 
{
  public function setUp() {
    $migrate = new TestAuthMigration('quiet');
    $migrate->up();
    $this->model = new TestUser();
    $this->model1 = new TestUser();
    $this->model1->update_attributes($this->get_fixture("user1"));
  }
  
  public function tearDown() {
    $migrate = new TestAuthMigration)'quiet';
    $migrate->down();
  }
  
  protected function get_fixture($type) {
    $fixtures = array(
      "user1" => array("username"=>"test1", "password"=>"password", "email"=>"test1@test.com"),
      "user2" => array("username"=>"test2", "password"=>"password", "email"=>"test2@test.com"),
      "user3" => array("username"=>"test1", "password"=>"password", "email"=>"test3@test.com")
    );
    return $fixtures[$type];
  }
  
  public function test_verification() {
    $auth = new DBAuthorise();
    $auth->database_table="TestUser";
    $this->assertTrue($auth->verify("test1","password"));
    $auth->logout();
    $this->assertFalse($auth->is_logged_in());
    $this->assertFalse($auth->verify("test1","badpassword"));
  }
  
}

?>