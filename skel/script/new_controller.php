#!/usr/bin/php
<?php
$cntdir = dirname(__FILE__).'/../app/controller/.';
$controller_name = $argv[0];
$content = "<?php
class {$controller_name}Controller extends ApplicationController
{
	
}
?>
";
$command = "echo ".$content." > ".$cntdir.$controller_name.".php";
echo $command; exit;
system("$command");


?>