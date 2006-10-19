#!/usr/bin/php
<?php
$cntdir = dirname(__FILE__).'/../app/controller/.';
$controller_name = $argv[1];
$content = "<?php
class {$controller_name}Controller extends ApplicationController
{
	
}
?>
";
system('echo "{$content}" > $cntdir.$controller_name.php');


?>