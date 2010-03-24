<?php
/**
	*  This file sets up the application.
	*  Sets up constants for the main file locations.
  *  @package PHP-Wax
	*/

/**
 *	Defines application level constants
 */
if(!defined("WAX_START_TIME")) define("WAX_START_TIME",microtime(TRUE));
if(!defined("WAX_START_MEMORY")) define("WAX_START_MEMORY",memory_get_usage());
if(!defined("APP_DIR")) define('APP_DIR', WAX_ROOT . "app/");
if(!defined("MODEL_DIR")) define('MODEL_DIR' , WAX_ROOT.'app/model/');
if(!defined("CONTROLLER_DIR")) define('CONTROLLER_DIR', WAX_ROOT.'app/controller/');
if(!defined("FORMS_DIR")) define('FORMS_DIR', WAX_ROOT.'app/forms/');
if(!defined("CONFIG_DIR")) define('CONFIG_DIR' , WAX_ROOT.'app/config/');
if(!defined("VIEW_DIR")) define('VIEW_DIR', WAX_ROOT.'app/view/');
if(!defined("APP_LIB_DIR")) define('APP_LIB_DIR', WAX_ROOT.'app/lib/');
if(!defined("CACHE_DIR")) define('CACHE_DIR', WAX_ROOT.'tmp/cache/');
if(!defined("LOG_DIR")) define('LOG_DIR', WAX_ROOT.'tmp/log/');
if(!defined("SESSION_DIR")) define('SESSION_DIR', WAX_ROOT.'tmp/session/');
if(!defined("PUBLIC_DIR")) define('PUBLIC_DIR', WAX_ROOT.'public/');
if(!defined("SCRIPT_DIR")) define('SCRIPT_DIR', PUBLIC_DIR.'javascripts/');
if(!defined("STYLE_DIR")) define('STYLE_DIR', PUBLIC_DIR.'stylesheets/');
if(!defined("PLUGIN_DIR")) define('PLUGIN_DIR', WAX_ROOT . 'plugins/'); 
if(!defined("FRAMEWORK_DIR")) define("FRAMEWORK_DIR", dirname(__FILE__));
if(function_exists('date_default_timezone_set')){
  if(!defined('PHPWAX_TIMEZONE')) date_default_timezone_set('Europe/London');
  else date_default_timezone_set(PHPWAX_TIMEZONE);
}

/**
 * check cache
 *
 */
function auto_loader_check_cache(){
  
  $cache_location = CACHE_DIR .'layout/';
  $image_cache_location = CACHE_DIR.'images/';
  include_once FRAMEWORK_DIR .'/utilities/Session.php';
  include_once FRAMEWORK_DIR .'/utilities/Spyc.php';
  include_once FRAMEWORK_DIR .'/utilities/Config.php';
  include_once FRAMEWORK_DIR .'/cache/WaxCacheLoader.php';
  include_once FRAMEWORK_DIR .'/interfaces/CacheEngine.php';
  include_once FRAMEWORK_DIR .'/cache/engines/WaxCacheFile.php';
  include_once FRAMEWORK_DIR .'/cache/engines/WaxCacheImage.php';
  include_once FRAMEWORK_DIR .'/utilities/File.php';  
  $mime_types = array("json" => "text/javascript", 'js'=> 'text/javascript', 'xml'=>'application/xml', 'html'=>'text/html', 'kml'=>'application/vnd.google-earth.kml+xml');
  
  /** CHECK LAYOUT CACHE **/
  if($config = Config::get('layout_cache')){    
    if(isset($config['lifetime'])) $cache = new WaxCacheLoader('File', $cache_location, $config['lifetime']);
    else $cache = new WaxCacheLoader('File', $cache_location);
    if($content = $cache->layout_cache_loader($config)){
      $url_details = parse_url("http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
      $pos = strrpos($url_details['path'], ".");
      $ext = substr($url_details['path'],$pos+1); 
      if(isset($mime_types[$ext])) header("Content-type:".$mime_types[$ext]);
      header("wax-cache: true");
      echo $content;
      exit;
    }
  }  
  /** ALSO CHECK FOR IMAGES **/
  if($img_config = Config::get('image_cache') && substr_count($_SERVER['REQUEST_URI'], 'show_image')){
    if(isset($img_config['lifetime'])) $cache = new WaxCacheLoader('Image', $image_cache_location, $img_config['lifetime']);
    else $cache = new WaxCacheLoader('Image', $image_cache_location);
    if($cache->valid($img_config)) File::display_image($cache->identifier);
  }  
  
  return false;
}	


function __autoload($class_name) {
  AutoLoader::include_from_registry($class_name);
}

function throw_wxexception($e) {
	$exc = new WXException($e->getMessage(), "Application Error");
}

function throw_wxerror($code, $error) {
	$exc = new WXException($error, "Application Error $code");
}


class WaxRecursiveDirectoryIterator extends RecursiveDirectoryIterator {
  
  public function hasChildren() {
    if(substr($this->getFilename(),0,1)==".") return false;
    else return parent::hasChildren();
  }
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
  static $plugin_asset_types = array('images'=>"images", 'javascripts'=>"javascripts", 'stylesheets'=>"stylesheets");
  /**
   *  The registry allows classes to be registered in a central location.
   *  A responsibility chain then decides upon include order.
   *  Format $registry = array("responsibility"=>array("ClassName", "path/to/file"))
   */
  static public $registry = array();
  static public $registry_chain = array("user", "application", "plugin", "framework");
  static public $controller_registry = array();
  static public $view_registry = array();
  
  static public function add_asset_type($key, $type){
    self::$plugin_asset_types[$key] = $type;
  }
  static public function register($responsibility, $class, $path) {
    self::$registry[$responsibility][$class]=$path;
  }
  
  static public function register_controller_path($responsibility, $path) {
    self::$controller_registry[$responsibility][]=$path;
  }
  static public function register_view_path($responsibility, $path) {
    self::$view_registry[$responsibility][]=$path;
  }
  
  static public function include_from_registry($class_name) {
    foreach(self::$registry_chain as $responsibility) {
      if(isset(self::$registry[$responsibility]) && array_key_exists($class_name, self::$registry[$responsibility])) {
        if(require_once(self::$registry[$responsibility][$class_name]) ) { return true; }
      }
    }
   	throw new WaxDependencyException("Class Name - {$class_name} cannot be found in the registry.", "Missing Dependency");
	}
	
	static public function controller_paths($resp=false) {
	  if($resp) return self::$controller_registry[$resp];
	  foreach(self::$controller_registry as $responsibility) {
      foreach($responsibility as $path) $paths[]=$path;
    }
    return $paths;
	}
	static public function view_paths($resp = false) {
	  if($resp) return self::$view_registry[$resp];
	  foreach(self::$view_registry as $responsibility) {
      foreach($responsibility as $path) $paths[]=$path;
    }
    return $paths;
	}
	
	static public function include_plugin($plugin) {
	  self::recursive_register(PLUGIN_DIR.$plugin."/lib", "plugin");
	  self::recursive_register(PLUGIN_DIR.$plugin."/resources/app/controller", "plugin");
	  self::register_controller_path("plugin", PLUGIN_DIR.$plugin."/lib/controller/");
	  self::register_controller_path("plugin", PLUGIN_DIR.$plugin."/resources/app/controller/");
	  self::register_view_path("plugin", PLUGIN_DIR.$plugin."/view/");
		$setup = PLUGIN_DIR.$plugin."/setup.php";
		self::$plugin_array[] = array("name"=>"$plugin","dir"=>PLUGIN_DIR.$plugin);
		if(is_readable($setup)) include_once($setup);
	}
	
	static public function plugin_installed($plugin) {
		return is_readable(PLUGIN_DIR.$plugin);
	}
	
	static public function detect_inis(){
	  if(is_readable(PLUGIN_DIR)){
	    foreach(glob(PLUGIN_DIR.'*') as $file){
	      if(is_dir($file) && is_readable($file) && is_readable($file."/ini.php")) include $file."/ini.php";
	    }
	  }
	}
	
	static public function autoregister_plugins() {
	  if(defined('AUTOREGISTER_PLUGINS')) return false;
	  if(is_readable(PLUGIN_DIR)){
	    $plugins = scandir(PLUGIN_DIR);
	    sort($plugins);
	    foreach($plugins as $plugin) {
	      if(is_dir(PLUGIN_DIR.$plugin) && substr($plugin, 0, 1) != ".") self::include_plugin($plugin);
	    }
    }
  }
	
	static public function detect_assets() {
	  self::register("framework", "File", FRAMEWORK_DIR."/utilities/File.php");
	  if(!isset($_GET["route"])) return false;
	  $temp_route = $_GET["route"];
	  $_temp_route= preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $temp_route);
	  while(strpos($temp_route, "..")) $temp_route= str_replace("..", ".", $temp_route);
	  $asset_paths = explode("/", $_GET["route"]);
	  if(in_array($asset_paths[0], self::$plugin_asset_types)) {
	    $plugins = scandir(PLUGIN_DIR);
	    $type = array_shift($asset_paths);
  	  rsort($plugins);
  	  foreach($plugins as $plugin) {
  	    if(!is_file($plugin) && substr($plugin,0,1) != "."){
	        $path = PLUGIN_DIR.$plugin."/resources/public/".$type."/".implode("/", $asset_paths);
	        $mime = File::mime_map($path);
	        if(is_readable($path)){
	          $mime = File::mime_map($path);
	          switch($type){
	            case "images": File::display_image($path);break;
	            default: File::display_asset($path, $mime); break;
            }
          }
        }
  	  }
	  }
	}
	
	static public function recursive_register($directory, $type, $force = false) {
	  if(!is_dir($directory)||substr($directory,0,1)==".") { return false; }
	  $dir = new RecursiveIteratorIterator(
		            $dirit = new WaxRecursiveDirectoryIterator($directory), true);
		foreach ( $dir as $file ) {
		  if(substr($fn = $file->getFilename(),0,1) != "." && strrchr($fn, ".")==".php") {
		    if($force){
		      require_once($file->getPathName());
	      }else{
  		    $classname = basename($fn, ".php");
  			  self::register($type, $classname, $file->getPathName());
		    }
			}	
		}
	}
	
	static public function detect_test_mode() {
	  if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == "simpletest" ) {
	    define('ENV', 'test');
	  }
	}
	
	static public function detect_environments() {
	  if(!is_array(Config::get("environments"))) return false;
	  if($_SERVER['HOSTNAME']) $addr = gethostbyname($_SERVER['HOSTNAME']);
	  elseif($_SERVER['SERVER_ADDR']) $addr = $_SERVER['SERVER_ADDR'];
	  if($envs= Config::get("environments")) {
	    foreach($envs as $env=>$range) {
  	    $range = "/".str_replace(".", "\.", $range)."/";
  	    if(preg_match($range, $addr) && !defined($env) ) {
  	      define('ENV', $env);
  	    } 
  	  }
	  }
	}

	static public function register_helpers($classes = false) {
	  if(!$classes) $classes = get_declared_classes();
	  foreach($classes as $class) {
	    if(is_subclass_of($class, "WaxHelpers") || $class=="WaxHelpers") {
	      foreach(get_class_methods($class) as $method) {
	        if(substr($method,0,1)!="_" && !function_exists($method)) WaxCodeGenerator::new_helper_wrapper($class, $method);
	      }
	    }
	  }
	}

	static public function initialise() {	
	  self::detect_inis();
		self::detect_assets();
	  self::detect_test_mode();
	  self::recursive_register(APP_LIB_DIR, "user");
	  self::recursive_register(MODEL_DIR, "application");
	  self::recursive_register(CONTROLLER_DIR, "application");
	  self::recursive_register(FORMS_DIR, "application");
		self::recursive_register(FRAMEWORK_DIR, "framework");
		WaxEvent::run("wax.start");
		self::register_controller_path("user", CONTROLLER_DIR);
		self::register_view_path("user", VIEW_DIR);
		self::autoregister_plugins();
		self::include_from_registry('Inflections');  // Bit of a hack -- forces the inflector functions to load
		self::include_from_registry('WXHelpers');  // Bit of a hack -- forces the helper functions to load
		self::register_helpers();
		set_exception_handler('throw_wxexception');
		set_error_handler('throw_wxerror', 247 );
		WaxEvent::run("wax.init");
	}
	/**
	 *	Includes the necessary files and instantiates the application.
	 *	@access public
	 */	
	static public function run_application($environment="development", $full_app=true) {
	  //if(!defined('ENV')) define('ENV', $environment);	
		$app=new WaxApplication($full_app);
	}

	/**** DEPRECIATED FUNCTIONS BELOW THIS POINT, WILL BE REMOVED IN COMING RELEASES ****/

	static public function include_dir($directory, $force = false) {
	  return self::recursive_register($directory, "framework", $force);
	}
	
}
auto_loader_check_cache();
Autoloader::initialise();

