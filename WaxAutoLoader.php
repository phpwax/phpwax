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
   * some file types should be sniffed for
   */
  public static $remapped_assets = array();
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
    $dir = new RecursiveIteratorIterator( $dirit = new WaxRecursiveDirectoryIterator($directory), true);
    foreach($dir as $file){
      if(basename($file) ==  WaxAutoLoader::$ini_file){
        WaxAutoLoader::$inis[] = $file;
        include_once $file;
      }
    }
  }
    
  /**
   * Main function
   */
  public static function initialise(){
    WaxAutoLoader::constants();
    WaxAutoLoader::inis();
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
 * to remap a certain file to an alternative location you can use this regex based mapping
 */                                    
WaxAutoLoader::$


?>