#!/usr/bin/php
<?php
$appdir = dirname(__FILE__).'/../app/';
$controller_name = $argv[1];
$content = "<?php
class {$controller_name}Controller extends ApplicationController
{
	
}
?>";
system("echo {$content} >> $appdir.$controller_name.php");


?>