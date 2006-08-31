<?php
define('APP_DIR', dirname(dirname(__FILE__)).'/');
define('FW_NAME', 'wax');
require_once(FW_NAME.'/AutoLoader.php');
AutoLoader::run_application();
?>