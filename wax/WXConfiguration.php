<?php
/**
 * 	@package php-wax
 */

/**
 *
 * @package php-wax
 *
 *  Exposes the application configuration to other classes.
 *  
 *  
 *  The default method is to use load_yaml but since this only returns an array
 *  then it can easily be replaced with other methods.
 *  
 *  This is a Singleton object which once initialised cannot be duplicated, 
 *  the inject_configuration() method allows infinite possibilites to alter the runtime
 *  environment, either by loading another config file or overwriting via php.
 *
 *  @author Ross Riley
 *
 */
class WXConfiguration
{
	
	static private $config_array;
	static private $app_yaml_file=false;
	static private $instance=false;
	
	
	static public function set_instance($initial_config) {
	  if(self::$instance) return false;
	  self::$instance=new WXConfiguration();
		if(!$initial_config) $initial_config = CONFIG_DIR."config.yml";
	  self::$instance::$app_yaml_file = $initial_config;
	  self::$instance::$config_array = self::load_yaml(self::$app_yaml_file);
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
		if($config=="all") return self::$config_array;
		$config=explode("/", $config);
		$confarray=self::$config_array;
		foreach($config as $conf) {
			$confarray=$confarray[$conf];
		}
		if($confarray) {
		  return $confarray; 
		}
		return false;
	}
	
	static public function replace_yaml($file) {
	  $config = new WXConfiguration;
	  self::$config_array = self::load_yaml($file);
	}
	
	/**
    *  Allows you to change the configuration on the fly. Use either a file or PHP array.
    *  @return bool
    */
	
	static public function set($new_config = array()) {
	  $config = new WXConfiguration;
	  if(is_array($new_config)) {
	    self::$config_array = array_merge(self::$config_array, $new_config);
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
	  $config = new WXConfiguration;
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
	  $config = new WXConfiguration;
	  return $config->return_config($value);
	}
		
	
}

?>