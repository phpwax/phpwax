#!/usr/bin/php
<?php
$cntdir = dirname(__FILE__).'/../app/controller/';
$controller_name = $argv[1];
if(strpos($controller_name, "/")) {
	$cont = explode($controller_name, "/");
	$cntdir = $cntdir.$cont[0]."/";
	system("mkdir -p {$cntdir}");
	$controller_name = ucfirst($cont[0]).ucfirst($cont[1]);
}
$content = "<?php
class {$controller_name}Controller extends ApplicationController
{
	
}
?>
";
$command = "echo ".'"'.$content.'"'." > ".$cntdir.$controller_name."Controller.php";
system($command);


?>