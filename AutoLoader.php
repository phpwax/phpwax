<?php
/**
 *  This file sets up the application.
 *  Sets up constants for the main file locations.
 *  @package PHP-Wax
 */

/**
 * WaxCacheTrigger to look for cache triggers - legacy
 */
class WaxCacheTrigger{
  public static $mime_types = array("json" => "text/javascript", 'js'=> 'text/javascript', 'xml'=>'application/xml', 'rss'=> 'application/rss+xml', 'html'=>'text/html', 'kml'=>'application/vnd.google-earth.kml+xml');
  public static $yaml = true;
  
  public function __construct(){
    $cache_location = CACHE_DIR .'layout/';
    $image_cache_location = CACHE_DIR .'images/';
    include_once FRAMEWORK_DIR .'/utilities/Session.php';
    if(WaxCacheTrigger::$yaml) include_once FRAMEWORK_DIR .'/utilities/Spyc.php';
    include_once FRAMEWORK_DIR .'/utilities/Config.php';
    include_once FRAMEWORK_DIR .'/cache/WaxCacheLoader.php';
    include_once FRAMEWORK_DIR .'/interfaces/CacheEngine.php';
    include_once FRAMEWORK_DIR .'/cache/engines/WaxCacheFile.php';
    include_once FRAMEWORK_DIR .'/cache/engines/WaxCacheImage.php';
    include_once FRAMEWORK_DIR .'/utilities/File.php';
  }
  
  public function layout(){   
    if(($config = Config::get('layout_cache')) && $config['engine']){
      if($_REQUEST['no-wax-cache']) return false;
  		if($config['include_path']) include_once WAX_ROOT .$config['include_path'] .'WaxCache'.$config['engine'].'.php';
  		else include_once FRAMEWORK_DIR .'/cache/engines/WaxCache'.$config['engine'].'.php';
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
  }

  public function image(){
    if(($img_config = Config::get('image_cache')) && substr_count($_SERVER['REQUEST_URI'], 'show_image') && $img_config['engine']){
  		if($img_config['include_path']) include_once WAX_ROOT .$img_config['include_path'] .'WaxCache'.$img_config['engine'].'.php';
  		else include_once FRAMEWORK_DIR .'/cache/engines/WaxCache'.$img_config['engine'].'.php';
      if(isset($img_config['lifetime'])) $cache = new WaxCacheLoader($img_config['engine'], $image_cache_location, $img_config['lifetime']);
      else $cache = new WaxCacheLoader('Image', $image_cache_location);
      if($cache->valid($img_config)) File::display_image($cache->identifier);
    }
  }
}
/**
 * Check if this is a test client
 */
class WaxTestMode{
  public function active(){
    if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == "simpletest" ) define('ENV', 'test');
  }
}
/**
 * set the date & time values
 */
class WaxDateTime{
  public function set_defaults(){
    if(function_exists('date_default_timezone_set')){
      if(!defined('PHPWAX_TIMEZONE')) date_default_timezone_set('Europe/London');
      else date_default_timezone_set(PHPWAX_TIMEZONE);
    }
  }
}
/**
 *
 */
class WaxPluginResources{
  public static $plugin_asset_types = array('images'=>"images", 'javascripts'=>"javascripts", 'stylesheets'=>"stylesheets");
  
  public function rewrite(){
	  if(!isset($_GET["route"])) return false;
    include_once FRAMEWORK_DIR."/utilities/File.php";
	  $temp_route = $_GET["route"];
	  $_temp_route= preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $temp_route);
	  while(strpos($temp_route, "..")) $temp_route= str_replace("..", ".", $temp_route);
	  $asset_paths = explode("/", $_GET["route"]);
	  if(in_array($asset_paths[0], WaxPluginResources::$plugin_asset_types)) {
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
}
/**
 * MAIN AUTOLOADER
 */
class AutoLoader{

  /**
   * list of all constants to create should look like - sorted by keys
   * - CONSTANT_NAME => array('parent'=>PARENT_CONSTANT, 'value'=>$VALUE, 'function'=>function_name, 'params'=>params_to_pass_to_function);
   */
  public static $wax_constants =  array(
                                       'WAX_START_TIME' => array('function'=>'microtime', 'params'=>true),
                                       'WAX_START_MEMORY' => array('function'=>'memory_get_usage'),
                                       'APP_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'app/'),
                                       'MODEL_DIR' => array('parent'=>'APP_DIR', 'value'=>'model/'),
                                       'CONTROLLER_DIR' => array('parent'=>'APP_DIR', 'value'=>'controller/'),
                                       'FORMS_DIR' => array('parent'=>'APP_DIR', 'value'=>'forms/'),
                                       'CONFIG_DIR' => array('parent'=>'APP_DIR', 'value'=>'config/'),
                                       'VIEW_DIR' => array('parent'=>'APP_DIR', 'value'=>'view/'),
                                       'APP_LIB_DIR' => array('parent'=>'APP_DIR', 'value'=>'lib/'),
                                       'TMP_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'tmp/'),
                                       'CACHE_DIR' => array('parent'=>'TMP_DIR', 'value'=>'cache/'),
                                       'LOG_DIR' => array('parent'=>'TMP_DIR', 'value'=>'log/'),
                                       'SESSION_DIR' => array('parent'=>'TMP_DIR', 'value'=>'session/'),
                                       'PUBLIC_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'public/'),
                                       'SCRIPT_DIR' => array('parent'=>'PUBLIC_DIR', 'value'=>'javascripts/'),
                                       'STYLE_DIR' => array('parent'=>'PUBLIC_DIR', 'value'=>'stylesheets/'),
                                       'PLUGIN_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'plugins/')
                                       );
  /**
   * ini file to look for and the results
   */
  public static $ini_file = "ini.php";
  public static $inis = array();
  /**
   * list of functions that can be called as a pre hook key array - ie remapping certain urls for the cms, adding in cache, etc
   * First key is the path to the file, that has an array of classes which is an array of functions
   * /path/to/file/from/wax_root => array('class_name_1'=> array('func_1', 'func_to_call_2'), 'class_name_2'=>array('func_3'))
   */
  public static $pre_functions = array(
                                      'wax/AutoLoader.php' => array(
                                                                  'WaxDateTime'=>array('set_defaults'), 
                                                                  'WaxCacheTrigger'=> array('layout', 'image'), 
                                                                  'WaxTestMode'=>array('active'),
                                                                  'WaxPluginResources'=>array('rewrite')
                                                                  )
                                    );
  //class registry info
  public static $register_file_ext = ".php";
  public static $registry_directories = array("APP_DIR","FRAMEWORK_DIR","PLUGIN_DIR");
  public static $registered_classes = array();
  public static $loaded_classes = array('AutoLoader');
  //paths to all the folders containing controllers
  public static $controller_paths = array();
  //all folders containing views
  public static $view_registry = array();
  //array of all plugins inside the plugin folder
  public static $plugins = array();
  public static $plugin_setup_file = "setup.php";

  //register all the constants
  public static function constants(){
    foreach(AutoLoader::$wax_constants as $name=>$info){
      $value = false;
      $parent = ($info['parent']) ? constant($info['parent']) : "";
      if($info['value']) $value = $info['value'];
      elseif($info['function'] && $info['params']) $value = call_user_func($info['function'], $info['params']);
      elseif($info['function']) $value = call_user_func($info['function']);
      if(!defined($name)) define($name, $parent.$value);
    }
  }
  /**
   * register & include the inis
   */
  public static function inis(){
    foreach(scandir(PLUGIN_DIR) as $item){
      if(is_dir(PLUGIN_DIR.$item) && is_readable(PLUGIN_DIR.$item) && is_readable(PLUGIN_DIR.$item."/".AutoLoader::$ini_file) && is_file(PLUGIN_DIR.$item."/".AutoLoader::$ini_file)){
        AutoLoader::$inis[] = PLUGIN_DIR.$item."/".AutoLoader::$ini_file;
        include_once PLUGIN_DIR.$item."/".AutoLoader::$ini_file;
      }
    }
  }
  /**
   * Run over the pre init hooks - cache would a good one
   */
  public static function pre_init_hooks(){
    foreach(AutoLoader::$pre_functions as $path=>$classes){
      if(is_readable(WAX_ROOT.$path)){
        include_once WAX_ROOT.$path;
        foreach($classes as $class=>$functions){
          $obj = new $class;
          foreach($functions as $func) $obj->$func();
        }
      }
    }
  }
  
  public static function controller_paths(){
    return AutoLoader::$controller_paths;    
  }
  /**
   * loop over all registered directories and add the files to the class listing
   * will also add to controller list as well
   */
  public static function register($registry = false, $constant=true){
    if(!$registry) $registry = AutoLoader::$registry_directories;
    foreach($registry as $d){
      if($constant) $d = constant($d);
      if(is_readable($d) && is_dir($d)){
        $dir = new RecursiveIteratorIterator(new RecursiveRegexIterator(new RecursiveDirectoryIterator($d), '#(?<!/)\.php$|^[^\.]*$#i'), true); //the god maker
        foreach($dir as $file){
          $path = $file->getPathName();
          $classname = basename($path, ".php");
          AutoLoader::$registered_classes[$classname] = $path;
          // check for this being a controller
          if(strpos($path, "/controller/") !== false) AutoLoader::$controller_paths[] = substr($path,0,strrpos($path, "/controller/")+12);
        }
      }
    }
    AutoLoader::$controller_paths = array_unique(AutoLoader::$controller_paths);
  }
  //scans over the plugins top level folders and adds them to the stacks
  public static function plugins(){
    if(is_readable(PLUGIN_DIR)){
	    $plugins = scandir(PLUGIN_DIR);
	    sort($plugins);
	    foreach($plugins as $plugin) {
	      if(is_dir(PLUGIN_DIR.$plugin) && substr($plugin, 0, 1) != "."){ //if it looks like a plugin
	        AutoLoader::$plugins[$plugin] = PLUGIN_DIR.$plugin; //add to the main array
	        AutoLoader::$view_registry["plugin"][] = PLUGIN_DIR.$plugin."/view/"; //add the view dir to the stack
	        if(is_file(PLUGIN_DIR.$plugin."/".AutoLoader::$plugin_setup_file)) include_once PLUGIN_DIR.$plugin."/".AutoLoader::$plugin_setup_file;
	      }
	    }
    }
  }

  public static function view_paths($type=false){
    if($type) return AutoLoader::$view_registry[$type];
    $views= array();
    foreach(AutoLoader::$view_registry as $k=>$paths) foreach($paths as $i=>$v) $views[]=$v;
    return $views;
  }
  public static function register_views($path = false, $type="user"){
    if(!$path) $path = VIEW_DIR;
    AutoLoader::$view_registry[$type][] = $path;
  }
  //globalise the helper functions
  public static function register_helpers($classes = array()) {
	  if(!count($classes)) $classes = get_declared_classes();
	  foreach((array)$classes as $class) {
	    if(is_subclass_of($class, "WXHelpers") || $class=="WXHelpers" || $class=="Inflections") {
	      foreach(get_class_methods($class) as $method) {
	        if(substr($method,0,1)!="_" && !function_exists($method)) WaxCodeGenerator::new_helper_wrapper($class, $method);
	      }
	    }
	  }
	}
  //magic function that loads the class in
  public static function include_from_registry($class){
    if(AutoLoader::$registered_classes[$class] && !AutoLoader::$loaded_classes[$class]){
      include AutoLoader::$registered_classes[$class];
      AutoLoader::$loaded_classes[$class] = AutoLoader::$registered_classes[$class];
    }elseif(!AutoLoader::$registered_classes[$class]){
      throw new Exception("$class no found");
    }
  }

  //legacy function
  public static function add_asset_type($key, $type){
    WaxPluginResources::$plugin_asset_types[$key]=$type;
  }
  
  /**
   * MAIN FUNCTIONS
   */
  public static function initialise(){
    AutoLoader::constants();
    AutoLoader::inis();
    AutoLoader::pre_init_hooks();
    AutoLoader::register();
    WaxEvent::run("wax.start");
    AutoLoader::plugins();
    AutoLoader::register_views();
    //force loading of inflections
    AutoLoader::include_from_registry("Inflections");
    AutoLoader::include_from_registry("WXHelpers");
    AutoLoader::register_helpers();    
    WaxEvent::run("wax.init");
  }
  
  static public function run_application($environment="development", $full_app=true) {
	  //if(!defined('ENV')) define('ENV', $environment);
		$app=new WaxApplication($full_app);
	}

}


/**
 *
 */
function __autoload($class_name) {
  AutoLoader::include_from_registry($class_name);
}

//run the initialise!
AutoLoader::initialise();

?>