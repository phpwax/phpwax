<?
/* Uncomment this line to always run on the latest nightly build */
//define('WAX_EDGE', 'true');

define('WAX_VERSION', '0.6');
define('WAX_ROOT', dirname(dirname(dirname(__FILE__)))."/" );
if(is_dir(WAX_ROOT."wax")) {
	define('FRAMEWORK_DIR', WAX_ROOT."wax");
} elseif(defined("WAX_EDGE")) {
	ini_set('include_path', ini_get("include_path").":/home/waxphp/trunk");
	define('FRAMEWORK_DIR', "/home/waxphp/trunk/wax");
} else {
	ini_set('include_path', ini_get("include_path").":/home/waxphp/tags/".WAX_VERSION);
	define('FRAMEWORK_DIR', "/home/waxphp/tags/".WAX_VERSION."/wax");
}
ini_set('include_path', ini_get("include_path").":".WAX_ROOT);
require_once(FRAMEWORK_DIR."/AutoLoader.php");

/* Add your application level commands below here */

//AutoLoader::include_plugin("cmscore");


/* Locations for error Redirects 
// Page not found error
// WXRoutingException::$redirect_on_error = "";

// Application Error and an email address and subject to send details to.
//WXException::$redirect_on_error = "";
//WXException::$email_on_error="";
//WXException::$email_subject_on_error="";

?>