<?
/* Uncomment this line to always run on the latest nightly build */
//define('WAX_EDGE', 'true');

define('WAX_VERSION', '0.6');
define('WAX_ROOT', dirname(dirname(__FILE__)) );
ini_set('include_path', ini_get("include_path").":".WAX_ROOT);
if(defined("WAX_EDGE")) {
	ini_set('include_path', ini_get("include_path").":/home/waxphp/trunk/");
} else {
	ini_set('include_path', ini_get("include_path").":/home/waxphp/tags/".WAX_VERSION);
}

//AutoLoader::add_plugin_directory("cms");
?>