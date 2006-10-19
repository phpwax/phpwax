#!/usr/bin/php
<?php
$testdir = dirname(__FILE__).'/../app/tests/';
$test_name = ucfirst($argv[1]);
$content = "<?php
class Test{$test_name} extends WXTestCase
{
  public function setUp() {}
	
  public function tearDown() {}

  /* Add tests below here. all must start with the word 'test'
}
?>
";
$command = "echo ".'"'.$content.'"'." > ".$testdir."Test".$test_name.".php";
system($command);

?>