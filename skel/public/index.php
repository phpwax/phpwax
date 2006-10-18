<?php
ini_set('include_path', ini_get("include_path").":".dirname(dirname(__FILE__)));
require_once('app/config/environment.php');
require_once('wax/AutoLoader.php');
AutoLoader::run_application();
?>