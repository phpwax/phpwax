#! /usr/bin/php

<?php

$priority_array = array(
  "db/WaxRecordset.php",
  "exceptions/WaxSqlException.php",
  "template/WaxTemplate.php"
);
$ignore_dirs = array("tests", "testing");
$compile_to = dirname(__FILE__)."/wax.php";
$comp = fopen($compile_to,"w");
fwrite($comp,"<?php");
foreach($priority_array as $pri) {
  $code = file_get_contents(dirname(__FILE__)."/$pri");
  $code = str_replace("<?php", "", $code);
  $code = str_replace("?>", "", $code);
  $code = str_replace("<?", "", $code);
  fwrite($comp, $code);
}
$dir = new RecursiveIteratorIterator(
            $dirit = new RecursiveDirectoryIterator(dirname(__FILE__)), true);
foreach ( $dir as $file ) {
  if($file->getFilename()=="wax.php") continue;
  if($file->getFilename()=="make.php") continue;
  $rname = str_replace(dirname(__FILE__)."/", "",$file->getPathname());
  $rpath = str_replace(dirname(__FILE__)."/", "",$file->getPath());
  if(strpos($rname, "/deprecated/")) continue;
  if( in_array($rname, $priority_array)) continue;
  if( in_array($rpath, $ignore_dirs)) continue;
  if(substr($fn = $file->getFilename(),0,1) != "." && strrchr($fn, ".")==".php") {
    $code = file_get_contents($file->getPathname());
    $code = str_replace("<?php", "", $code);
    $code = str_replace("?>", "", $code);
    $code = str_replace("<?", "", $code);
    fwrite($comp, $code);
	}	
}
fwrite($comp, "?>");
fclose($comp);
?>