<?php
require_once(FRAMEWORK_DIR."/Core/Loader.php");
require_once(FRAMEWORK_DIR."/Core/ApplicationLoader.php");
require_once(FRAMEWORK_DIR."/Core/FrameworkLoader.php"); 

use Wax\Core\FrameworkLoader;
use Wax\Core\ApplicationLoader;
use Wax\Utilities\CodeGenerator;

$loader = new ApplicationLoader();
$loader->register();

$loader = new FrameworkLoader();
$loader->register_namespace("Wax",FRAMEWORK_DIR);
$loader->register();

function waxexception($e) {
  $exc = new Wax\Core\Exception($e->getMessage(), "Application Error", false, array("file"=>$e->getFile(), "line"=>$e->getLine(), "trace"=>$e->getTraceAsString()));
}

function waxerror($code, $error, $file, $line, $vars) {
  //log warnings without halting execution
  if($code == 2) Wax\Utilities\Log::log("warn", "code: $code, error: $error, file: $file, line: $line");
  else $exc = new Wax\Core\Exception($error, "Application Error $code", false, array("file"=>$file, "line"=>$line, "vars"=>$vars));
}
set_exception_handler('waxexception');
set_error_handler('waxerror', 247 );


CodeGenerator::helper_wrappers("Wax\Template\Helper\AssetTagHelper", ["js_bundle","css_bundle"]);

