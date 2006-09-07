<?php
/**
	*  This file sets up the application.
	*  Sets up constants for the main file locations.
  *  @package wx.php.core
	*/

/**
 *	Defines application level constants
 */
define('FRAMEWORK_DIR', WAX_ROOT . "wax");
define('APP_DIR', WAX_ROOT . "app");
define('MODEL_DIR' , WAX_ROOT.'app/model/');
define('CONTROLLER_DIR', WAX_ROOT.'app/controller/');
define('VIEW_DIR', WAX_ROOT.'app/view/');
define('CACHE_DIR', WAX_ROOT.'tmp');

function __autoload($class_name) {
	switch(TRUE) {
		case is_readable(FRAMEWORK_DIR.$class_name . ".php"): 
			include_once(FRAMEWORK_DIR.$class_name . ".php"); break;
		case is_readable(APP_DIR.'lib/'.$class_name.".php"):
			include_once(APP_DIR.'lib/'.$class_name.".php"); break;
		case is_readable(MODEL_DIR.$class_name.".php"):
			include_once(MODEL_DIR.$class_name.".php"); break;
		case is_readable(CONTROLLER_DIR.$class_name.".php"):
			include_once(CONTROLLER_DIR.$class_name.".php"); break;
		default:
		  require_once($class_name. ".php");
	}
} 

/**
 *	A simple static class to Preload php files and commence the application.
 *	@package wx.php.core
 *	@static
 */
class AutoLoader
{
/**
 *	@access public
 *	@param string $dir The directory to include 
 */
	static public function include_dir($dir) {
		$fileArray=scandir($dir);
	  foreach($fileArray as $file) {
			if(is_file($file) && is_readable($file)) {
				require_once($file);
			} else {
				if(preg_match("/^[a-zA-Z0-9_-]+\.php/",$file, $match)) { 
					if(!require_once($dir."/".$match[0])) {
						throw new exception("Cannot include file - ".$include);
					}
				}
			}
	  }
		return true;
	}
	
	/**
	 *	Includes the necessary files and instantiates the application.
	 *	@access public
	 */	
	static public function run_application() {
		AutoLoader::include_dir(FRAMEWORK_DIR);		
		AutoLoader::include_dir(MODEL_DIR);				
		AutoLoader::include_dir(CONTROLLER_DIR);
		ConfigBase::set_instance();	
		$app=new ApplicationBase;
	}

}

?>