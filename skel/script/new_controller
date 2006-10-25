#!/usr/bin/php
<?php
$cntdir = dirname(__FILE__).'/../app/controller/';
$viewdir = dirname(__FILE__).'/../app/view/'.$argv[1];
$controller_name = $argv[1];
if(strpos($controller_name, "/")) {
	$cont = explode("/", $controller_name);
	$cntdir = $cntdir.$cont[0]."/";
	system("mkdir -p {$cntdir}");
	$controller_name = ucfirst($cont[0]).ucfirst($cont[1]);
} else {
	$controller_name = ucfirst($argv[1]);
}
$content = "<?php
class {$controller_name}Controller extends ApplicationController
{
	
}
?>
";
if(is_readable($cntdir.$controller_name."Controller.php")) {
  exit("[ERROR] Not written, a controller of that name already exists.
");
}
$command = "echo ".'"'.$content.'"'." > ".$cntdir.$controller_name."Controller.php";
system($command);
system("mkdir -p {$viewdir}");
echo "Controller class created in app/controller.
";

?>