#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/../app/config/environment.php';
$output_dir = WAX_ROOT."doc";
$source_dir1 = WAX_ROOT."app";
$source_dir2 = FRAMEWORK_DIR;

$command = "phpdoc -t {$output_dir} -d {$source_dir1},{$source_dir2} -o HTML:frames:phpedit";
system($command);
echo "Documentation has been created in /doc. Open the index.html file to view.
";
?>