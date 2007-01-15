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
define('CONFIG_DIR' , WAX_ROOT.'app/config/');
define('VIEW_DIR', WAX_ROOT.'app/view/');
define('APP_LIB_DIR', WAX_ROOT.'app/lib/');
define('CACHE_DIR', WAX_ROOT.'tmp/cache/');
define('SESSION_DIR', WAX_ROOT.'tmp/session/');
define('PUBLIC_DIR', WAX_ROOT.'public/');
define('SCRIPT_DIR', PUBLIC_DIR.'javascripts/');
define('STYLE_DIR', PUBLIC_DIR.'stylesheets/');
define('PLUGIN_DIR', WAX_ROOT . 'plugins/'); 

function __autoload($class_name) {
  AutoLoader::include_from_registry($class_name);
}

function throw_wxexception($e) {
	$exc = new WXException($e->getMessage(), "Application Error");
}

function throw_wxerror($code, $error) {
	$exc = new WXException($error, "Application Error $code");
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
  static public $registry_chain = array("user", "application", "plugin", "framework");
  static public $production_server = false;
  
  static public function register($responsibility, $class, $path) {
    self::$registry[$responsibility][$class]=$path;
  }
  
  static public function include_from_registry($class_name) {
    foreach(self::$registry_chain as $responsibility) {
      if(isset(self::$registry[$responsibility]) && array_key_exists($class_name, self::$registry[$responsibility])) {
        if(require_once(self::$registry[$responsibility][$class_name]) ) { return true; }
      }
    }
   	throw new WXDependencyException("Class Name - {$class_name} cannot be found in the registry.", "Missing Dependency");
	}
	
	static public function include_plugin($plugin) {
	  self::recursive_register(PLUGIN_DIR.$plugin."/lib", "plugin");
	}
	
	static public function recursive_register($directory, $type) {
	  if(!is_dir($directory)) { return false; }
	  $dir = new RecursiveIteratorIterator(
		           new RecursiveDirectoryIterator($directory), true);
		foreach ( $dir as $file ) {
		  if(substr($file->getFilename(),0,1) != "." && strrchr($file->getFilename(), ".")==".php") {
		    $classname = substr($file->getFilename(), 0, strrpos($file->getFilename(), "."));
			  self::register($type, $classname, $file->getPathName());
			}	
		}
	}
	
	static public function add_plugin_directory($plugin) {
	  self::include_plugin($plugin);
	}
	
	static public function include_dir($directory, $force = false) {
		if($force) {
			foreach(scandir($directory) as $file) {
				if(strpos($file, ".php")) require_once($directory."/".$file);
			}
		}
	  return self::recursive_register($directory, "framework");
	}
	
	static public function detect_test_mode() {
	  if($_SERVER['HTTP_USER_AGENT'] == "simpletest" ) {
	    define('ENV', 'test');
	  }
	}
	
	static public function detect_production_mode() {
	  if(self::$production_server) {
	    if($_SERVER['SERVER_ADDR'] == self::$production_server) define('ENV', 'production');
	  }
	}

	static public function register_helpers() {
	  foreach(get_declared_classes() as $class) {
	    if(is_subclass_of($class, "WXHelpers") || $class=="WXHelpers" || $class == "WXInflections") {
	      foreach(get_class_methods($class) as $method) {
	        if(substr($method,0,1)!="_" && !function_exists($method)) {
	          WXGenerator::new_helper_wrapper($class, $method);
          }
	      }
	    }
	  }
	}
	
	static public function initialise() {
	  self::detect_test_mode();
	  self::detect_production_mode();
	  die("HELLO");
	  self::recursive_register(APP_LIB_DIR, "user");
	  self::recursive_register(MODEL_DIR, "application");
	  self::recursive_register(CONTROLLER_DIR, "application");
		self::recursive_register(FRAMEWORK_DIR, "framework");
		WXConfiguration::set_instance();
		self::include_from_registry('WXInflections');  // Bit of a hack -- forces the inflector functions to load
		self::include_from_registry('WXHelpers');  // Bit of a hack -- forces the helper functions to load
		self::register_helpers();
		set_exception_handler('throw_wxexception');
		set_error_handler('throw_wxerror', 247 );
	}
	/**
	 *	Includes the necessary files and instantiates the application.
	 *	@access public
	 */	
	static public function run_application() {	  
		$app=new WXApplication;
	}

}

Autoloader::initialise();

?>