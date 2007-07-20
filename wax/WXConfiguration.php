<?php
/**
 * 	@package PHP-Wax
 */

/**
 *
 * @package PHP-Wax
 *
 *  Exposes the application configuration to other classes.
 *  
 *  
 *  The default method is to use load_yaml but since this only returns an array
 *  then it can easily be replaced with other methods.
 *  
 *  This is a Singleton object which once initialised cannot be duplicated, 
 *  the set() method allows infinite possibilites to alter the runtime
 *  environment, either by loading another config file or overwriting via php.
 *
 *  @author Ross Riley
 *
 */
class WXConfiguration
{
	
	public $config_array;
	public $app_yaml_file=false;
	static private $instance=false;
	
	static public function set_instance($initial_config=false) {
	  if(self::$instance) return false;
	  self::$instance=new WXConfiguration();
		if(!$initial_config) $initial_config = CONFIG_DIR."config.yml";
	  self::$instance->app_yaml_file = $initial_config;
	  self::$instance->config_array = self::$instance->load_yaml(self::$instance->app_yaml_file);
	}
	
	/**
    *  Loads any .yml file
    *  @return array
    */
	static private function load_yaml($config_file) {	
		if(is_readable($config_file)){
		  return Spyc::YAMLLoad($config_file);
	  } else {
		  return false;
    }	
	}
	
	
	/**
    *  The clever function. Returns the configuration array for the particular 
    *  portion of the file you want - or if you specify 'all' as the parameter, the whole array.
    *  @return array
    */
	
	public function return_config($config=null) {
		if($config=="all") return self::$instance->config_array;
		$config=explode("/", $config);
		$confarray=self::$instance->config_array;
		foreach($config as $conf) {
			if(array_key_exists($conf,$confarray)) $confarray=$confarray[$conf];
			else $confarray=false;
		}
		if($confarray) {
		  return $confarray; 
		}
		return false;
	}
	
	static public function replace_yaml($file) {
	  self::$instance->config_array = self::$instance->load_yaml($file);
	}
	
	/**
    *  Allows you to change the configuration on the fly. Use either a file or PHP array.
    *  @return bool
    */
	
	static public function set($new_config = array()) {
	  if(is_array($new_config)) {
	    self::$instance->config_array = array_merge(self::$instance->config_array, $new_config);
	    return true;
	  } 
	  return false;
	}
	
	/**
    *  An environment is a sub-array of the configuration, this simply copies the environment array
    *  to the root of the configuration overwriting anything that gets in its way.
    *  @return bool
    */
	
	static public function set_environment($env) {
	  $env = self::get($env);
	  if(is_array($env)) {
	    return self::set($env);
	  }
	  return false;
	}
	
	/**
    *  @return array
    */
	
	static public function get($value) { 
	  return self::$instance->return_config($value);
	}
		
	
}

?>