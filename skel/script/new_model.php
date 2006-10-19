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
if(is_readable($modeldir.$model_name.".php")) {
  exit("[ERROR] Not written, a model of that name already exists.
");
}
$command = "echo ".'"'.$content.'"'." > ".$modeldir.$model_name.".php";
system($command);

?>