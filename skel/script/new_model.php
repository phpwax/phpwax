#!/usr/bin/php
<?php
$modeldir = dirname(__FILE__).'/../app/model/';
$model_name = ucfirst($argv[1]);
$content = "<?php
class {$model_name} extends WXActiveRecord
{
	
}
?>
";
$command = "echo ".'"'.$content.'"'." > ".$modeldir.$model_name.".php";
system($command);

?>