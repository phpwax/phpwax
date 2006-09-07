<?php
define('WAX_ROOT', dirname(dirname(__FILE__)).'/');
ini_set('include_path', WAX_ROOT);
require_once('wax/AutoLoader.php');
AutoLoader::run_application();
?>