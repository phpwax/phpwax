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
 * Redirection of CMS Assets
 */



class WaxAutoLoader{

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
  
  
  //array of all plugins inside the plugin folder
  public static $plugins = array();

  /**
   * register all the constants
   */
  public static function constants(){
    foreach(WaxAutoLoader::$wax_constants as $name=>$info){
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
      if(basename($file) ==  WaxAutoLoader::$ini_file){
        WaxAutoLoader::$inis[] = $file;
        include_once $file;
      }
    }
  }  
  /**
   * Run over the pre init hooks - cache would a good one
   */
  public static function pre_init_hooks(){
    foreach(WaxAutoLoader::$pre_functions as $path=>$classes){
      if(is_readable(WAX_ROOT.$path)){
        include_once WAX_ROOT.$path;
        foreach($classes as $class=>$functions){
          $obj = new $class;
          foreach($functions as $func) $obj->$func();
        }
      }
    }
  }
  
  /**
   * Main function
   */
  public static function initialise(){
    WaxAutoLoader::constants();
    WaxAutoLoader::inis();
    //lets you do caching & remapping
    WaxAutoLoader::pre_init_hooks();
    exit;
  }
}

/**
 * load the static array with default constants that applications use
 */
WaxAutoLoader::$wax_constants = array(
                                    'WAX_START_TIME' => array('function'=>'microtime', 'params'=>true),
                                    'WAX_START_MEMORY' => array('function'=>'memory_get_usage'),
                                    'APP_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'app/'),
                                    'MODEL_DIR' => array('parent'=>'APP_DIR', 'value'=>'model/'),
                                    'CONTROLLER_DIR' => array('parent'=>'APP_DIR', 'value'=>'app/'),
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
WaxAutoLoader::$pre_functions = array(
                                    'wax/WaxAutoLoader.php' => array('WaxCacheTrigger'=> array('layout', 'image'))
                                  );

?>