<?php
/**
 *  This file sets up the application.
 *  Sets up constants for the main file locations.
 *  @package PHP-Wax
 */

/**
 * Custom iterator - excludes any git files, hidden config files etc
 */
class WaxRecursiveDirectoryIterator extends RecursiveDirectoryIterator {
  public function hasChildren() {
    if(substr($this->getFilename(),0,1)==".") return false;
    else return parent::hasChildren();
  }
}

/**
 * WaxCacheTrigger to look for cache triggers - legacy
 */
class WaxCacheTrigger{
  public static $mime_types = array("json" => "text/javascript", 'js'=> 'text/javascript', 'xml'=>'application/xml', 'rss'=> 'application/rss+xml', 'html'=>'text/html', 'kml'=>'application/vnd.google-earth.kml+xml');
  public static $yaml = true;

  public function layout(){
    $cache_location = CACHE_DIR .'layout/';
    include_once FRAMEWORK_DIR .'/utilities/Session.php';
    if(WaxCacheTrigger::$yaml) include_once FRAMEWORK_DIR .'/utilities/Spyc.php';
    include_once FRAMEWORK_DIR .'/utilities/Config.php';
    include_once FRAMEWORK_DIR .'/cache/WaxCacheLoader.php';
    include_once FRAMEWORK_DIR .'/interfaces/CacheEngine.php';
    include_once FRAMEWORK_DIR .'/cache/engines/WaxCacheFile.php';
    include_once FRAMEWORK_DIR .'/utilities/File.php';

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
    $image_cache_location = CACHE_DIR .'images/';
    include_once FRAMEWORK_DIR .'/utilities/Session.php';
    if(WaxCacheTrigger::$yaml) include_once FRAMEWORK_DIR .'/utilities/Spyc.php';
    include_once FRAMEWORK_DIR .'/utilities/Config.php';
    include_once FRAMEWORK_DIR .'/cache/WaxCacheLoader.php';
    include_once FRAMEWORK_DIR .'/interfaces/CacheEngine.php';
    include_once FRAMEWORK_DIR .'/cache/engines/WaxCacheFile.php';
    include_once FRAMEWORK_DIR .'/cache/engines/WaxCacheImage.php';
    include_once FRAMEWORK_DIR .'/utilities/File.php';
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
 * MAIN AUTOLOADER
 */
class AutoLoader{

  /**
   * list of all constants to create should look like - sorted by keys
   * - CONSTANT_NAME => array('parent'=>PARENT_CONSTANT, 'value'=>$VALUE, 'function'=>function_name, 'params'=>params_to_pass_to_function);
   */
  public static $wax_constants = array();
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
  public static $pre_functions = array();
  //class registry info
  public static $register_file_ext = ".php";
  public static $registry_directories = array();
  public static $registered_classes = array();
  public static $loaded_classes = array('AutoLoader');
  
  public static $controller_paths = array();

  //array of all plugins inside the plugin folder
  public static $plugins = array();
  public static $plugin_setup_file = "setup.php";
  /**
   * register all the constants
   */
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
    $dir = new RecursiveIteratorIterator( $dirit = new WaxRecursiveDirectoryIterator(PLUGIN_DIR), true);
    foreach($dir as $file){
      if(basename($file->getFilename()) ==  AutoLoader::$ini_file){
        AutoLoader::$inis[] = $file;
        include_once $file;
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
   */
  public static function register($registry = false, $constant=true){
    if(!$registry) $registry = AutoLoader::$registry_directories;
    foreach($registry as $d){
      if($constant) $d = constant($d);
      if(is_readable($d) && is_dir($d)){
        $dir = new RecursiveIteratorIterator(new WaxRecursiveDirectoryIterator($d), true);
        foreach($dir as $file){          
          if(substr($fn = $file->getFilename(),0,1) != "." && strrchr($fn, ".")==AutoLoader::$register_file_ext){
            $path = str_replace($fn, "", $file->getPathName());
            $classname = basename($fn, ".php");
            if(!AutoLoader::$registered_classes[$classname]) AutoLoader::$registered_classes[$classname] = $file->getPathName();
            //check for this being a controller
            if(strstr($classname, "Controller") && !AutoLoader::$controller_paths[$path]) AutoLoader::$controller_paths[$path] = $path;
          }
        }
      }
    }
  }

  public static function plugins(){
    if(is_readable(PLUGIN_DIR)){
	    $plugins = scandir(PLUGIN_DIR);
	    sort($plugins);
	    foreach($plugins as $plugin) {
	      if(is_dir(PLUGIN_DIR.$plugin) && substr($plugin, 0, 1) != "."){
	        //add to plugins list
	        AutoLoader::$plugins[$plugin] = PLUGIN_DIR.$plugin;
	        //add classes to register
	        AutoLoader::register(array(PLUGIN_DIR.$plugin."/"), false);
	        if(is_file(PLUGIN_DIR.$plugin."/".AutoLoader::$plugin_setup_file)) include_once PLUGIN_DIR.$plugin."/".AutoLoader::$plugin_setup_file;
	      }
	    }
    }
  }

  /**
   * globalise the helper functions
   */
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

  public static function include_from_registry($class){
    if(AutoLoader::$registered_classes[$class] && !AutoLoader::$loaded_classes[$class]){
      include AutoLoader::$registered_classes[$class];
      AutoLoader::$loaded_classes[$class] = AutoLoader::$registered_classes[$class];
    }elseif(!AutoLoader::$registered_classes[$class]){
      print_r(AutoLoader::$registered_classes);
      throw new Exception("$class no found");
    }
  }

  /**
   * Main function
   */
  public static function initialise(){
    AutoLoader::constants();
    AutoLoader::inis();
    //lets you do caching & remapping
    AutoLoader::pre_init_hooks();
    //load in all the files
    AutoLoader::register();
    WaxEvent::run("wax.start");
    //check for plugins
    AutoLoader::plugins();
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
 * load the static array with default constants that applications use
 */
AutoLoader::$wax_constants = array(
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
 * Load in the 2 default mapping functions used previously
 */
AutoLoader::$pre_functions = array(
                                    'wax/AutoLoader.php' => array('WaxCacheTrigger'=> array('layout', 'image'), 'WaxTestMode'=>array('active'))
                                  );
/**
 * Standard locations to register all files from
 */
AutoLoader::$registry_directories = array("APP_LIB_DIR", "MODEL_DIR", "CONTROLLER_DIR", "FORMS_DIR", "FRAMEWORK_DIR", "CONTROLLER_DIR", "PLUGIN_DIR");


/**
 *
 */
function __autoload($class_name) {
  AutoLoader::include_from_registry($class_name);
}

//run the initialise!
AutoLoader::initialise();

?>