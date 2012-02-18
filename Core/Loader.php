<?php
namespace Wax\Core;

/**
 * A simple static class to Preload php files and commence the application.
 * It manages a registry of PHP files and includes them according to hierarchy.
 * All file inclusion is done 'just in time' meaning that file load overhead is avoided.
 * @package PHP-Wax
 * @static
 */
class Loader {
  
  
  
  
  public $registry           = array();
  public $registry_chain     = array("user", "application", "plugin", "framework");
  public $registered_classes = array();
  public $loaded_classes     = array();
  public $namespaces         = array();
  public $namespace_fallbacks= array(
    "Config"        => "Wax\Config\Config",
    "WaxModel"      => "Wax\Model\Model",
    "WaxController" => "Wax\Dispatch\Controller",
  );
  
  
  public function __construct() {
    $this->constants();
  }
  
  
  //register all the constants
  public function constants(){
    foreach($this->constants as $name=>$info){
      if(defined($name)) continue;
      $value = false;
      $parent = ($info['parent']) ? constant($info['parent']) : "";
      if($info['value']) $value = $info['value'];
      elseif($info['function'] && $info['params']) $value = call_user_func($info['function'], $info['params']);
      elseif($info['function']) $value = call_user_func($info['function']);
      if(!defined($name)) define($name, $parent.$value);
    }
  }
  
  
  public function register_namespace($namespace,$directory) {
    $this->namespaces[$namespace]=$directory;
  }
  
  public function register_fallback($class, $alias) {
    $this->namespace_fallbacks[$class]=$alias;
  }
  
	
  
  public function find_file($class) {
    if ('\\' == $class[0]) $class = substr($class, 1);    
    // checking for namespaced class name
    if(false !== $pos = strrpos($class, '\\')) {      
      $namespace = substr($class, 0, $pos);
      $class_name = substr($class, $pos + 1);
      $normalized_class = str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $class_name).'.php';

      foreach($this->namespaces as $ns => $dir) {
        if (strpos($namespace, $ns) !== FALSE) {
          $mapped_path = preg_replace("/".$ns."/", $dir, $normalized_class, 1);
          if(is_file($mapped_path)) return $mapped_path;
        }
      }
      
      
    }
    
    
  }
  
  
  
  public function register_file($class, $file) {
    $this->registered_classes[$class] = $file;
  }
  
  public function load($class){
    $file = $this->find_file($class);      
    try {
      if(is_readable($file)) $res = include $file;
      if($res) return TRUE;
      if($res = $this->include_from_fallbacks($class)) return $res;
		} catch (Exception $e) {        				
      return FALSE;
		}
    return FALSE;
  }
  
  public function include_from_fallbacks($class) {
    foreach($this->namespace_fallbacks as $from=>$to) {
      if($from==$class) {
        class_alias($to, $from);
        return TRUE;
      }
    }
    return FALSE;
  }
  
  
  public function register() {
    spl_autoload_register(array($this, "load"));
  }
  
  public function register_helper_methods($class, $methods) {
    self::$helper_methods[$class]=$methods;
  }
  
  static public function include_helper_functions() {  
    CodeGenerator::helper_wrappers(self::$helper_methods);
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
  

 
  
}
