<?php


class Wax {
	
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
  public static $controller_paths = array();
  static public $view_registry = array();
  public static $register_file_ext = ".php";
  public static $registry_directories = array("APP_DIR","FRAMEWORK_DIR");
  public static $registered_classes = array();
  public static $loaded_classes = array('AutoLoader', "Wax");
  public static $ini_file = "ini.php";
  public static $inis = array();
  public static $plugins = array();
  public static $plugin_setup_file = "setup.php";
  
  //register all the constants
  public static function constants(){
    foreach(self::$wax_constants as $name=>$info){
      $value = false;
      $parent = ($info['parent']) ? constant($info['parent']) : "";
      if($info['value']) $value = $info['value'];
      elseif($info['function'] && $info['params']) $value = call_user_func($info['function'], $info['params']);
      elseif($info['function']) $value = call_user_func($info['function']);
      if(!defined($name)) define($name, $parent.$value);
    }
  }
  
  
  static public function add_asset_type($key, $type){
    self::$plugin_asset_types[$key] = $type;
  }

  /**
    * loop over all registered directories and add the files to the class listing
    * will also add to controller list as well
    */
  public static function register($registry = false, $constant=true){
    if(!$registry) $registry = self::$registry_directories;
    foreach($registry as $d){
      if($constant) $d = constant($d);
      if(is_readable($d) && is_dir($d)){        
        $dir = new RecursiveIteratorIterator(new RecursiveRegexIterator(new RecursiveDirectoryIterator($d, RecursiveDirectoryIterator::FOLLOW_SYMLINKS), '#(?<!/)\.php$|^[^\.]*$#i'), true); //the god maker
        foreach($dir as $file){
          $path = $file->getPathName();
          $classname = basename($path, self::$register_file_ext);
          if($file->isFile() && substr($path,-3)=="php") self::$registered_classes[$classname] = $path;
          // check for this being a controller
          if(strpos($path, "/controller/") !== false) self::$controller_paths[] = substr($path,0,strrpos($path, "/controller/")+12);
        }
      }
    }
    self::$controller_paths = array_unique(self::$controller_paths);
  }
  
  public static function register_directory($directory) {
    $dir = opendir($directory);
    while(false != ($file = readdir($dir))) {
      if(($file != ".") and ($file != "..") and (substr($file,0,1)!=".") ) {
        $filepath = $directory ."/". $file; 
        if(is_dir($filepath) && substr($file,0,1)!==".") self::register_directory($filepath);
        elseif(is_file($filepath) && substr($filepath,-3)=="php")  self::$registered_classes[basename($file, ".php")] = $filepath; // put in array.
      }   
    }
  }
  
  public static function register_file($file) {
    self::$registered_classes[basename($file, ".php")] = $file;
  }
  
	public static function load($module) {
		if(is_dir(FRAMEWORK_DIR."/".$module)) return self::register_directory(FRAMEWORK_DIR."/".$module);
		if(substr($module,-4)!==".php") $module .= ".php";
		if(is_readable(FRAMEWORK_DIR."/".$module)) self::register_file(FRAMEWORK_DIR."/".$module);
	}
  
  //scans over the plugins top level folders and adds them to the stacks
  public static function plugins(){
    if(is_readable(PLUGIN_DIR)){
	    $plugins = scandir(PLUGIN_DIR);
	    sort($plugins);
	    foreach($plugins as $plugin) {
	      if(is_dir(PLUGIN_DIR.$plugin) && substr($plugin, 0, 1) != "."){ //if it looks like a plugin
	        self::$plugins[$plugin] = PLUGIN_DIR.$plugin; //add to the main array
	        self::$view_registry["plugin"][] = PLUGIN_DIR.$plugin."/view/"; //add the view dir to the stack
	        self::$inis[] = PLUGIN_DIR.$plugin."/".self::$ini_file;
	        if(is_readable(PLUGIN_DIR.$plugin."/".self::$ini_file)) include_once PLUGIN_DIR.$plugin."/".self::$ini_file;
	        if(is_dir(PLUGIN_DIR.$plugin."/lib")) self::register_directory(PLUGIN_DIR.$plugin."/lib");
	        if(is_file(PLUGIN_DIR.$plugin."/".self::$plugin_setup_file)) include_once PLUGIN_DIR.$plugin."/".self::$plugin_setup_file;
	      }
	    }
    }
  }
  
  
  static public function register_controller_path($responsibility, $path) {
    self::$controller_registry[$responsibility][]=$path;
  }
  static public function register_view_path($responsibility, $path) {
    self::$view_registry[$responsibility][]=$path;
  }
  
  static public function include_from_registry($class){
    if(self::$registered_classes[$class] && !self::$loaded_classes[$class]){
      include self::$registered_classes[$class];
      self::$loaded_classes[$class] = self::$registered_classes[$class];
    }elseif(!self::$registered_classes[$class]){
			try {
				self::initialise();
				include_once self::$registered_classes[$class];
			} catch (Exception $e) {
				throw new WaxException("Class Name - {$class} cannot be found in the registry.");
			}
      
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
  

  
  static public function plugin_installed($plugin) { return is_readable(PLUGIN_DIR.$plugin); }
  

  static public function detect_test_mode() {
    if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == "simpletest" ) {
      define('ENV', 'test');
    }
  }


  static public function register_helpers($classes = array()) {
		Wax::load("utilities/WaxCodeGenerator");
    if(!count($classes)) $classes = get_declared_classes();
    foreach((array)$classes as $class) {
      if(is_subclass_of($class, "WXHelpers") || $class=="WXHelpers" || $class=="Inflections" || is_subclass_of($class,"WaxHelper")) {
        foreach(get_class_methods($class) as $method) {
          if(substr($method,0,1)!="_" && !function_exists($method)) WaxCodeGenerator::new_helper_wrapper($class, $method);
        }
      }
    }
  }

	static public function load_helper($helper) {
		if(!self::$registered_classes[$helper]) self::load($helper);
		self::include_from_registry($helper);
		self::register_helpers(array($helper));
	}

  static public function initialise() { 
    self::register();  
    self::plugins();  
    self::register_helpers(array("AssetTagHelper","UrlHelper","WaxCacheHelper","RequestHelper","WaxPartialHelper"));    
  }
  
  public function bare_initialise() {
    WaxEvent::run("wax.pre_init");
    self::constants();
    self::detect_test_mode();
    WaxEvent::run("wax.start");
    self::register_directory(FRAMEWORK_DIR."/core");
    self::register_directory(FRAMEWORK_DIR."/dispatch");
    self::register_directory(FRAMEWORK_DIR."/config");
    self::register_directory(APP_DIR."controller");
    self::register_file(FRAMEWORK_DIR."/utilities/Inflections.php");
    self::register_file(FRAMEWORK_DIR."/helpers/WXHelpers.php");
    self::register_file(FRAMEWORK_DIR."/utilities/Request.php");
    self::register_file(FRAMEWORK_DIR."/utilities/Session.php");
    self::register_file(FRAMEWORK_DIR."/template/WaxTemplate.php");
    self::register_helpers();
    self::register_controller_path("user", CONTROLLER_DIR);
    self::register_view_path("user", VIEW_DIR);
    WaxEvent::run("wax.init"); 
		set_exception_handler('throw_wxexception');
		set_error_handler('throw_wxerror', 247 );  
  }
  
  /**
   * Includes the necessary files and instantiates the application.
   * @access public
   */ 
  static public function run_application($environment="development", $full_app=true) {
    $app=new WaxApplication($full_app);
    if($app->is_light) {
      $app->execute();
    } else {
      self::initialise();
      $app->initialise_database();
	    $app->execute();
    }
  }
	
}