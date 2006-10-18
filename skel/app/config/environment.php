<?
/* Uncomment this line to always run on the latest nightly build */
//define('WAX_EDGE', 'true');

define('WAX_VERSION', '0.6');
define('WAX_ROOT', dirname(dirname(dirname(__FILE__)))."/" );
if(defined("WAX_EDGE")) {
	ini_set('include_path', ini_get("include_path").":/home/waxphp/trunk");
	define('FRAMEWORK_DIR', "/home/waxphp/trunk/wax");
} else {
	ini_set('include_path', ini_get("include_path").":/home/waxphp/tags/".WAX_VERSION);
	define('FRAMEWORK_DIR', "/home/waxphp/trunk/tags/".WAX_VERSION."/wax");
}
ini_set('include_path', ini_get("include_path").":".WAX_ROOT);


//AutoLoader::add_plugin_directory("cms");
?>