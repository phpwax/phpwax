<?php
/**
	*  This file sets up the application.
	*  Sets up constants for the main file locations.
  *  @package PHP-Wax
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
define('LOG_DIR', WAX_ROOT.'tmp/log/');
define('SESSION_DIR', WAX_ROOT.'tmp/session/');
define('PUBLIC_DIR', WAX_ROOT.'public/');
define('SCRIPT_DIR', PUBLIC_DIR.'javascripts/');
define('STYLE_DIR', PUBLIC_DIR.'stylesheets/');
define('PLUGIN_DIR', WAX_ROOT . 'plugins/'); 
if(!defined("FRAMEWORK_DIR")) define("FRAMEWORK_DIR", dirname(__FILE__));

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
 *  It manages a registry of PHP files and includes them according to hierarchy.
 *  All file inclusion is done 'just in time' meaning that file load overhead is avoided.
 *	@package PHP-Wax
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
		$setup = PLUGIN_DIR.$plugin."/setup.php";
		if(is_readable($setup)) include_once($setup);
	}
	
	static public function plugin_installed($plugin) {
		return is_readable(PLUGIN_DIR.$plugin);
	}
	
	static public function autoregister_plugins() {
	  if(defined('AUTOREGISTER_PLUGINS')) return false;
	  $plugins = scandir(PLUGIN_DIR);
	  sort($plugins);
	  foreach($plugins as $plugin) {
	    if(is_dir(PLUGIN_DIR.$plugin)) self::include_plugin($plugin);
	  }
	}
	
	static public function detect_assets() {
	  self::register("framework", "File", FRAMEWORK_DIR."/utilities/File.php");
	  if(!isset($_GET["route"])) return false;
	  $temp_route = $_GET["route"];
	  $_temp_route= preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $temp_route);
	  while(strpos($temp_route, "..")) $temp_route= str_replace("..", ".", $temp_route);
	  $asset_paths = explode("/", $_GET["route"]);
	  if($asset_paths[0] =="images" || $asset_paths[0] =="javascripts" || $asset_paths[0] =="stylesheets") {
	    $plugins = scandir(PLUGIN_DIR);
	    $type = array_shift($asset_paths);
  	  rsort($plugins);
  	  foreach($plugins as $plugin) {
  	    $path = PLUGIN_DIR.$plugin."/resources/public/".$type."/".implode("/", $asset_paths);
  	    if($type=="images") File::display_image($path);
  	    if($type=="javascripts") File::display_asset($path, "text/javascript");
  	    if($type=="stylesheets") File::display_asset($path, "text/css");
  	  }
	  }
	}
	
	static public function recursive_register($directory, $type) {
	  if(!is_dir($directory)) { return false; }
	  $dir = new RecursiveIteratorIterator(
		           new RecursiveDirectoryIterator($directory), true);
		foreach ( $dir as $file ) {
		  if(substr($file->getFilename(),0,1) != "." && strrchr($file->getFilename(), ".")==".php") {
		    $classname = basename($file->getFilename(), ".php");
			  self::register($type, $classname, $file->getPathName());
			}	
		}
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
	  if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == "simpletest" ) {
	    define('ENV', 'test');
	  }
	}
	
	static public function detect_environments() {
	  if(!is_array(WXConfiguration::get("environments"))) return false;
	  if($_SERVER['HOSTNAME']) $addr = gethostbyname($_SERVER['HOSTNAME']);
	  elseif($_SERVER['SERVER_ADDR']) $addr = $_SERVER['SERVER_ADDR'];
	  foreach(WXConfiguration::get("environments") as $env=>$range) {
	    $range = "/".str_replace(".", "\.", $range)."/";
	    if(preg_match($range, $addr) && !defined($env) ) {
	      define('ENV', $env);
	    } 
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
		self::detect_assets();
	  self::detect_test_mode();
	  self::recursive_register(APP_LIB_DIR, "user");
	  self::recursive_register(MODEL_DIR, "application");
	  self::recursive_register(CONTROLLER_DIR, "application");
		self::recursive_register(FRAMEWORK_DIR, "framework");
		self::autoregister_plugins();
		self::detect_environments();
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
	static public function run_application($environment="development", $full_app=true) {
	  //if(!defined('ENV')) define('ENV', $environment);	
		$app=new WXApplication($full_app);
	}

}
Autoloader::initialise();

?>