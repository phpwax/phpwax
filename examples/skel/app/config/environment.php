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

/* You can change the version number to run on older versions of the framework  */
define('WAX_VERSION', '0.8'); // This is set to your install path as created above.

/* You normally wouldn't change this line, unless you want to have the PEAR package somewhere unusual */
define('WAX_PATH', PEAR_INSTALL_DIR); 

/* Uncomment this line to always run on the development code. You will need to have regularly reinstall the PEAR package
 * full instructions are available at dev.php-wax.com. Don't do this for production sites. 
 */
//define('WAX_EDGE', 'true');



/************ Don't edit this section *******************************************************/
define('WAX_ROOT', dirname(dirname(dirname(__FILE__)))."/" );
if(is_dir(WAX_ROOT."wax")) {
	define('FRAMEWORK_DIR', WAX_ROOT."wax");
} elseif(defined("WAX_EDGE")) {
	ini_set('include_path', ini_get("include_path").":".WAX_PATH."/phpwax/trunk/wax");
	define('FRAMEWORK_DIR', WAX_PATH."/phpwax/trunk/wax");
} elseif(defined("WAX_VERSION")) {
  ini_set('include_path', ini_get("include_path").":".WAX_PATH."/phpwax/releases/".WAX_VERSION."/wax");
  define('FRAMEWORK_DIR', WAX_PATH."/phpwax/releases/".WAX_VERSION."/wax");
} else {
	ini_set('include_path', ini_get("include_path").":".WAX_PATH."/phpwax/releases/latest/wax");
	define('FRAMEWORK_DIR', WAX_PATH."/phpwax/releases/latest/wax");
}
ini_set('include_path', ini_get("include_path").":".WAX_ROOT);

/************* CACHE OUTSIDE OF FRAMEWORK ****************************************************
if($_SERVER['REMOTE_ADDR']){
  define('CACHE_DIR', WAX_ROOT."tmp/cache/");
  define('CONFIG_DIR', WAX_ROOT."app/config/");
  //start the session
  require_once FRAMEWORK_DIR.'/utilities/Session.php';
  Session::start();
  require_once FRAMEWORK_DIR.'/utilities/Spyc.php';
  require_once FRAMEWORK_DIR.'/utilities/Config.php';
  $layout_cache = Config::get('layout_cache');
  //if turned on then start the cache
  if(is_array($layout_cache) && count($layout_cache)){
    //set the cache dir
    
    $engine = $layout_cache['engine'];
    $layout_dir = CACHE_DIR . 'layout/';
    //load files
    require_once FRAMEWORK_DIR.'/cache/engines/WaxCache'.$engine.'.php';
    require_once FRAMEWORK_DIR.'/cache/WaxCacheLoader.php';
    //setup loader
    if(is_numeric($layout_cache['lifetime'])) $cache = new WaxCacheLoader($engine, $layout_dir, $layout_cache['lifetime']);
    else $cache = new WaxCacheLoader($engine, $layout_dir);
    
    //check if allowed
    if(!$cache->excluded($layout_cache) && $cache->layout_cache_loader()){
      echo $cache->layout_cache_loader();
      exit;
    }
  }
  
}
*********************************************************************************************/

//load the framework
require_once(FRAMEWORK_DIR."/AutoLoader.php");
/*********************************************************************************************/



/************ Application Error Handling *******************************************************
*
*  When you're running in production mode, you don't want your errors displayed to users.
*  The following commands can be uncommented to handle errors professionally.
*  Firstly the routing redirect_on_error gives a location for a 404 error (page not found)
*  The second redirect_on_error is an application error page.
*  Both of these can be either actions in your application or static pages.
*  
*  Finally email_on_error accepts an email address and email_subject_on_error a text subject.
*  If these are set a copy of the error trace will be emailed to the address. */

WXRoutingException::$redirect_on_error = "/404.html"; // Page not found error

// Application Error and an email address and subject to send details to.
WXException::$redirect_on_error = "/error.html";
//WXException::$email_on_error="";
//WXException::$email_subject_on_error="";
/*********************************************************************************************/


/*********************************************************************************************/

/*********** Your Additional Application Configuration ***************************************
*  This file is run at boot time so if you want to set any systemwide configuration values, 
*  you can do so below this point */





