<?
/* When running sites on the PHP-WAX framework you have the option to have a single installation
 * which all applications share, or you can have a separate version of the framework files inside
 * each site folder. If you've downloaded a packaged version with a 'wax' folder in your site root,
 * then you don't need to worry about any of these options.
 *
 * You can run a server-wide install from a PEAR installation. How-tos can be found on the PHP-WAX site.
 * There are two packages currently available, phpwax and phpwax-devel.
 * The config below, sets up which version you want to use. 
 */
 
define('WAX_PATH', '/home/waxphp/'); // This is set to your install path as created above.
define('WAX_EDGE', 'true'); // Uncomment this line to always run on the latest code.(best not do this for production sites)
//define('WAX_VERSION', '0.7'); // This runs a tag version..


/* Don't edit this section */
define('WAX_ROOT', dirname(dirname(dirname(__FILE__)))."/" );
if(is_dir(WAX_ROOT."wax")) {
	define('FRAMEWORK_DIR', WAX_ROOT."wax");
} elseif(defined("WAX_EDGE")) {
	ini_set('include_path', ini_get("include_path").":".WAX_PATH."trunk");
	define('FRAMEWORK_DIR', WAX_PATH."trunk/wax");
} else {
	ini_set('include_path', ini_get("include_path").":".WAX_PATH."tags/".WAX_VERSION);
	define('FRAMEWORK_DIR', WAX_PATH."tags/".WAX_VERSION."/wax");
}
ini_set('include_path', ini_get("include_path").":".WAX_ROOT);
require_once(FRAMEWORK_DIR."/AutoLoader.php");

/* Add your application level commands below here */

//AutoLoader::include_plugin("cmscore");


/* Locations for error Redirects */

// WXRoutingException::$redirect_on_error = ""; // Page not found error

// Application Error and an email address and subject to send details to.
//WXException::$redirect_on_error = "";
//WXException::$email_on_error="";
//WXException::$email_subject_on_error="";

?>