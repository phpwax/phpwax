<?php
define('WAX_ROOT', (dirname(dirname(__FILE__)))."/" );
define('CACHE_DIR', WAX_ROOT.'tmp/cache/');

$use_cache = false; //set to false by default
$cache_time = 60*60*1; //length of time to cache by
$mtime = 0; //file modified time
$cache_file = CACHE_DIR.str_replace("-", "_",$_SERVER['HTTP_HOST']).md5($_SERVER['REQUEST_URI']) .".layout.cache"; //file name that the waxcache would create

if(is_readable($cache_file)) $mtime = filemtime($cache_file);
if(count($_POST)) $use_cache = false; //so if any data has been posted clear it

if($use_cache){	
	echo file_get_contents($cache_file);
	exit;
}else{
	require_once dirname(__FILE__).'/../app/config/environment.php';
	AutoLoader::run_application();
}
?>