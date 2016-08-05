<?php
/**
  *  This file sets up the application.
  *  Sets up constants for the main file locations.
  *  @package PHP-Wax
  */

/**
 *  Defines application level constants
 */
if(!defined("WAX_ROOT")) define("WAX_ROOT", __DIR__."/");
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
if(!defined("PLUGIN_DIR")) define('PLUGIN_DIR', WAX_ROOT.'plugins/'); 
if(!defined("FRAMEWORK_DIR")) define("FRAMEWORK_DIR", dirname(__FILE__));
if(function_exists('date_default_timezone_set')){
  if(!defined('PHPWAX_TIMEZONE')) date_default_timezone_set('Europe/London');
  else date_default_timezone_set(PHPWAX_TIMEZONE);
}

// Setup Autoloader Stack
spl_autoload_register(array('AutoLoader',"include_from_registry"));

/**
 * check cache
 *
 */
function auto_loader_check_cache(){
  
  $cache_location = CACHE_DIR .'layout/';
  $image_cache_location = CACHE_DIR.'images/';
  $mime_types = array("css"=>"text/css","json" => "text/javascript", 'js'=> 'text/javascript', 'xml'=>'application/xml', 'rss'=> 'application/rss+xml', 'html'=>'text/html', 'kml'=>'application/vnd.google-earth.kml+xml');
  /** CHECK LAYOUT CACHE **/
  if(($config = Config::get('layout_cache')) && $config['engine']){
    if($_REQUEST['no-wax-cache']) return false;
    $cache = new WaxCacheLoader($config, $cache_location);

    if($content = $cache->layout_cache_loader($config)){
      $url_details = parse_url("http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
      $pos = strrpos($url_details['path'], ".");
      $ext = substr($url_details['path'],$pos+1); 
      if(isset($mime_types[$ext])) header("Content-type:".$mime_types[$ext]);
      header("wax-cache: true");
      header("wax-cache-eng: ".$config['engine']);
      header("wax-cache-id: ".str_replace(CACHE_DIR, "", $cache->identifier()));
      echo $content;
      exit;
    }
  }  
  /** ALSO CHECK FOR IMAGES **/
  if(($img_config = Config::get('image_cache')) && substr_count($_SERVER['REQUEST_URI'], 'show_image') && $img_config['engine']){
    if(isset($img_config['lifetime'])) $cache = new WaxCacheLoader($img_config['engine'], $image_cache_location, $img_config['lifetime']);
    else $cache = new WaxCacheLoader('Image', $image_cache_location);
    if($cache->valid($img_config)) File::display_image($cache->identifier);
  }  
  
  return false;
}

function throw_wxexception($e) {
  $exc = new WaxException($e->getMessage(), "Application Error", false, array("file"=>$e->getFile(), "line"=>$e->getLine(), "trace"=>$e->getTraceAsString()));
}

function throw_wxerror($code, $error, $file, $line, $vars) {
  //log warnings without halting execution
  if($code == 2) WaxLog::log("warn", "code: $code, error: $error, file: $file, line: $line");
  else $exc = new WaxException($error, "Application Error $code", false, array("file"=>$file, "line"=>$line, "vars"=>$vars));
}


class WaxRecursiveDirectoryIterator extends RecursiveDirectoryIterator {
  
  public function hasChildren() {
    if(substr($this->getFilename(),0,1)==".") return false;
    else return parent::hasChildren();
  }
}

/**
 * A simple static class to Preload php files and commence the application.
 * It manages a registry of PHP files and includes them according to hierarchy.
 * All file inclusion is done 'just in time' meaning that file load overhead is avoided.
 * @package PHP-Wax
 * @static
 */
class AutoLoader
{
/**
 *  @access public
 *  @param string $dir The directory to include 
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
  static public $asset_server = false;
  static public $initialised = false;
  static public $bootstrapped_app = false;
  static public $plugin_setup_scripts = array();
  static public $plugins_initialised = false;
  
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
  
  static public function get_asset_server() {
    if(!self::$asset_server) self::$asset_server = new Wax\Asset\AssetServer;
    return self::$asset_server;
  }
  
  static public function register_assets($bundle, $directory, $type = NULL) {
    $as = self::get_asset_server();
    $as->register($bundle, $directory, $type);
  }
  
  
  static public function include_from_registry($class_name) {
    if(class_exists($class_name)) return true;
    foreach(self::$registry_chain as $responsibility) {
      if(isset(self::$registry[$responsibility]) && array_key_exists($class_name, self::$registry[$responsibility])) {
        if(class_exists($class_name,false)) return true;
        if(require_once(self::$registry[$responsibility][$class_name]) ) {return true; }
      }
    }
    /*** If this fails, and we aren't initialised, try autoregistering the plugins ****/
    if(!self::$plugins_initialised) {
      self::autoregister_plugins();
      if(self::include_from_registry($class_name)) return true;
    }
    if(!self::$initialised) {
      self::initialise();
      if(self::include_from_registry($class_name)) return true;
    }
    
      
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
    if(is_readable($setup)) self::add_plugin_setup_script( $setup ) ;
  }
  
  static public function run_plugin_setup_scripts() {
    foreach(self::$plugin_setup_scripts as $setup) require_once($setup);
  }
  
  static public function add_plugin_setup_script($setup) {
    self::$plugin_setup_scripts[] = $setup ;
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
    self::$plugins_initialised = true;
  }
  
  static public function detect_assets() {
    self::register("framework", "File", FRAMEWORK_DIR."/utilities/File.php");
    if(!isset($_GET["route"])) return false;
    $temp_route = $_GET["route"];
    $_temp_route= preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $temp_route);
    while(strpos($temp_route, "..")) $temp_route= str_replace("..", ".", $temp_route);
    $asset_paths = explode("/", $_GET["route"]);
    if(in_array($asset_paths[0], self::$plugin_asset_types) && is_dir(PLUGIN_DIR)) {
      $plugins = scandir(PLUGIN_DIR);
      $type = array_shift($asset_paths);
      rsort($plugins);
      foreach($plugins as $plugin) {
        if(!is_file($plugin) && substr($plugin,0,1) != "."){
          $path = PLUGIN_DIR.$plugin."/resources/public/".$type."/".implode("/", $asset_paths);
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
  
  static public function asset_servable() {
    $asset_path = $_GET["route"];
    $as = self::get_asset_server();
    if($as->handles($asset_path)) {
      $as->serve($asset_path);
    }
    
  }

  
  static public function recursive_register($directory, $type, $force = false) {
    if(!is_dir($directory)||substr($directory,0,1)==".") { return false; }
    $dir = new RecursiveIteratorIterator(
                $dirit = new WaxRecursiveDirectoryIterator($directory), true);
    foreach ( $dir as $file ) {
      if(substr($fn = $file->getFilename(),0,1) != "." && strrchr($fn, ".")==".php") {
       $classname = basename($fn, ".php");
        if($force){
          if(!class_exists($classname, false)) require_once($file->getPathName());
        }else{          
          self::register($type, $classname, $file->getPathName());
        }
      } 
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

  static public function register_helpers($classes = array()) {
    if(!count($classes)) $classes = get_declared_classes();
    foreach((array)$classes as $class) {
      if(is_subclass_of($class, "WXHelpers") || $class=="WXHelpers" || $class=="Inflections") {
        foreach(get_class_methods($class) as $method) {
          if(substr($method,0,1)!="_" && !function_exists($method)) WaxCodeGenerator::new_helper_wrapper($class, $method);
        }
      }
    }
  }

  static public function initialise() {
    self::detect_inis();
    self::detect_assets();
    if(!self::$bootstrapped_app) self::file_locators();
    WaxEvent::run("wax.start");
    self::register_controller_path("user", CONTROLLER_DIR);
    self::register_view_path("user", VIEW_DIR);
    self::autoregister_plugins();
    self::include_from_registry('Inflections');  // Bit of a hack -- forces the inflector functions to load
    self::include_from_registry('WXHelpers');  // Bit of a hack -- forces the helper functions to load
    self::register_helpers();
    set_exception_handler('throw_wxexception');
    set_error_handler('throw_wxerror', 247 );
    self::run_plugin_setup_scripts();
    WaxEvent::run("wax.init");
    self::$initialised = true;
  }
  
  static public function file_locators() {
    self::recursive_register(APP_LIB_DIR, "user");
    self::recursive_register(MODEL_DIR, "application");
    self::recursive_register(CONTROLLER_DIR, "application");
    self::recursive_register(FORMS_DIR, "application");
    self::recursive_register(FRAMEWORK_DIR, "framework");
  }
  
  static public function bootstrap() {
    self::asset_servable();
    self::$bootstrapped_app = true;
    auto_loader_check_cache();
    self::initialise();
    return true;
  }
  
  /**
   * Includes the necessary files and instantiates the application.
   * @access public
   */ 
  static public function run_application($environment="development", $full_app=true) {
    if(!self::$initialised) self::initialise();
    //if(!defined('ENV')) define('ENV', $environment);
    $app=new WaxApplication($full_app);
  }

  /**** DEPRECIATED FUNCTIONS BELOW THIS POINT, WILL BE REMOVED IN COMING RELEASES ****/

  static public function include_dir($directory, $force = false) {
    return self::recursive_register($directory, "framework", $force);
  }
  
}