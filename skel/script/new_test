#!/usr/bin/php
<?php
$testdir = dirname(__FILE__).'/../app/tests/';
$test_name = ucfirst($argv[1]);
$content = "<?php
class Test{$test_name} extends WXTestCase
{
  public function setUp() {}
	
  public function tearDown() {}

  /* Add tests below here. all must start with the word 'test' */
}
?>
";
if(is_readable($testdir."Test".$test_name.".php")) {
  exit("[ERROR] Not written, a test of that name already exists.
");
}
$command = "echo ".'"'.$content.'"'." > ".$testdir."Test".$test_name.".php";
system($command);
echo "Test class created in app/tests.
";

?>