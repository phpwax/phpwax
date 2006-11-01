<?php
/**
	*  This file sets up the application.
	*  Sets up constants for the main file locations.
  *  @package wx.php.core
	*/

/**
 *	Defines application level constants
 */
define('APP_DIR', WAX_ROOT . "app/");
define('MODEL_DIR' , WAX_ROOT.'app/model/');
define('CONTROLLER_DIR', WAX_ROOT.'app/controller/');
define('VIEW_DIR', WAX_ROOT.'app/view/');
define('APP_LIB_DIR', WAX_ROOT.'app/lib/');
define('CACHE_DIR', WAX_ROOT.'tmp/cache/');
define('SESSION_DIR', WAX_ROOT.'tmp/session/');
define('PUBLIC_DIR', WAX_ROOT.'public/');
define('SCRIPT_DIR', PUBLIC_DIR.'javascripts/');
define('STYLE_DIR', PUBLIC_DIR.'stylesheets/');
define('PLUGIN_DIR', WAX_ROOT . 'plugins/'); 


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
		  if(!is_readable($class_name. ".php" || !require_once($class_name. ".php")) ) {
				return false;
				throw new WXDependencyException("Cannot find ".$class_name. ".php in ".ini_get("include_path"));			
			}
	}
}

function throw_wxexception() {
	$exc = new WXException("An unknown error has occurred", "Application Error");
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
  static $plugin_array=array();
  
  /**
   *  The registry allows classes to be registered in a central location.
   *  A responsibility chain then decides upon include order.
   *  Format $registry = array("responsibility"=>array("ClassName", "path/to/file"))
   */
  static public $registry = array();
  static public $registry_chain = array("application", "plugin", "framework");
  
  static public function register($responsibility, $class, $path) {
    self::$registry[$responsibility]=array($class, $path);
  }
 
	static public function include_dir($dir) {
  	//get a list of any classes included within the plugins directory
  	$pluginClasses = get_declared_classes();	  
		$fileArray=scandir($dir);
	  foreach($fileArray as $file) {
			if(is_dir($dir.$file) && substr($file,0,1)!="." ) {
				self::include_dir($dir.$file);
			} else {
				if(preg_match("/^[a-zA-Z0-9_-]+\.php/",$file, $match)  ) { 
  				$className = str_ireplace(".php", "", $match[0]);
  				if( !in_array($className, $pluginClasses) && !class_exists($className) ) {
  					if(!require_once($dir."/".$match[0])) {
  						throw new WXException("Cannot include file - ".$include);
  					}
					}//end class exist check
				}//end preg match
			} //end if is_dir
	  }//end foreach
		return true;
	}
	
	static public function add_plugin_directory($directory="./") 
	{
    self::$plugin_array[]=$directory;
    $directory  = PLUGIN_DIR . $directory. "/lib/";
    $included   = true;
    $plugins    = glob($directory  ."*.php");    
    if(empty($plugins)){return false;}
    
    foreach($plugins as $file)
    {
      $name = str_ireplace(PLUGIN_DIR, "", $file);
      $name = str_ireplace($directory, "", $name);
      if(class_exists($name)){ throw new WXException("Cannot include plugin file - " . $name); }
      
      if(!require_once($file)) 
  		{
  			throw new WXException("Cannot include file - ".$include);
  		}
    }      
	}
	
	static public function include_plugin($plugin) {
	  self::recursive_register(PLUGIN_DIR.$plugin."/lib", "plugin");
	}
	
	static public function recursive_register($directory, $type) {
	  $dir = new RecursiveIteratorIterator(
		           new RecursiveDirectoryIterator($directory), true);
		foreach ( $dir as $file ) {
		  $directory = substr($file,0,strrpos($file, "/"));
		  $filename = substr(strrchr($file, "/"), 1);
		  if(substr($filename,0,1) != "." && strrchr($filename, ".")==".php") {
		    $classname = substr($filename, 0, strrpos($filename, "."));
			  self::register($type, $classname, $directory.$filename);
			}	
		}
		print_r(self::$registry); exit;
	}
	
	
	
	/**
	 *	Includes the necessary files and instantiates the application.
	 *	@access public
	 */	
	static public function run_application() {
		AutoLoader::include_dir(FRAMEWORK_DIR);
		set_exception_handler('throw_wxexception');
		set_error_handler('throw_wxexception', 247 );
		AutoLoader::include_dir(MODEL_DIR);				
		AutoLoader::include_dir(CONTROLLER_DIR);
		AutoLoader::include_dir(APP_LIB_DIR);
		WXConfigBase::set_instance();
		$app=new ApplicationBase;
	}

}

?>