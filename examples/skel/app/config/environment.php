<?
/********************************* DO NOT EDIT ***********************************************/
/* When running sites on the PHP-WAX framework you have have a separate version of the framework 
 * inside each site folder. If you've downloaded a packaged version with a 'wax' or 'phpwax' folder 
 * in your site root, then you don't need to worry about any of these options.
 */

define('WAX_ROOT', dirname(dirname(dirname(__FILE__)))."/" );
/***
 * uncomment this line if you want an absolute path and replace the constant with the path to your
 * you wax folder - TRAILING SLASH IS REQUIRED!!!!
 */ 
//define('WAX_ROOT', "/path/to/wax/folder/" );
/**
 * to change the relative path of wax folder then set this constant to the location you want
 * remember this has to be relative to the wax_root set above.. NO TRAILING SLASH
 */ 
define('WAX_DIR', 'wax');
if(is_dir(WAX_ROOT.WAX_DIR)) define('FRAMEWORK_DIR', WAX_ROOT.WAX_DIR);
elseif(is_dir(WAX_ROOT."phpwax")) define('FRAMEWORK_DIR', WAX_ROOT."phpwax");
else throw new Exception('PHP WAX folder does not exist!');

ini_set('include_path', ini_get("include_path").":".WAX_ROOT);

//load the framework
require_once(FRAMEWORK_DIR."/AutoLoader.php");


/************ Application Error Handling *******************************************************
*
*  When you're running in production mode, you don't want your errors displayed to users.
*  The following commands can be uncommented to handle errors professionally.
*  Firstly the routing redirect_on_error gives a location for a 404 error (page not found)
*  The second redirect_on_error is an application error page.
*  Both of these can be either actions in your application or static pages.
*  
*  Finally email_on_error accepts an email address and email_subject_on_error a text subject.
*  If these are set a copy of the error trace will be emailed to the address. 
*/

WXRoutingException::$redirect_on_error = "/404.html"; // Page not found error

// Application Error and an email address and subject to send details to.
WXException::$redirect_on_error = "/error.html";
//WXException::$email_on_error="";
//WXException::$email_subject_on_error="";
/*********************************************************************************************/


/*********************************************************************************************/

/*********** Your Additional Application Configuration ***************************************
*  This file is run at boot time so if you want to set any systemwide configuration values, 
*  you can do so below this point 
*/

