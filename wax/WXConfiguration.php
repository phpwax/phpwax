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
	
	private $config_array;
	private $app_yaml_file=false;
	static private $instance=false;
	
	function __construct() {
	  self::set_instance();
	  $this->app_yaml_file = CONFIG_DIR."config.yml";
	  $this->config_array = $this->load_yaml($this->app_yaml_file);			
	}
	
	static public function set_instance() {
		if(!self::$instance) {
			self::$instance=new WXConfigBase();
		}
	}
	
	public function replace_configuration($new_config) {
	  if(is_array($new_config)) {
	    $this->config_array = $new_config;
	    return true;
	  } elseif($new_config = $this->load_yaml($new_config)) {
      $this->config_array = $new_config;
  	  return true;
	  }
	  return false;
	}
	
	
	/**
    *  Loads any .yml file
    *  @return array
    */
	private function load_yaml($config_file) {	
		if(is_readable($config_file)){
		  return Spyc::YAMLLoad($config_file);
	  } else {
		  return false;
    }	
	}
	
	/**
    *  Allows you to change the configuration on the fly. Use either a file or PHP array.
    *  @return bool
    */
	
	public function inject_configuration($new_config) {
	  if(is_array($new_config)) {
	    $this->config_array = array_merge($this->config_array, $new_config);
	    return true;
	  } elseif($new_config = $this->load_yaml($new_config)) {
      $this->config_array = array_merge($this->config_array, $new_config);
  	  return true;
	  }
	  return false;
	}
	
	
	/**
    *  The clever function. Returns the configuration array for the particular 
    *  portion of the file you want - or if you specify 'all' as the parameter, the whole array.
    *  @return array
    */
	
	private function return_config($config=null) {
		if($config=="all") return $this->config_array;
		$config=explode("/", $config);
		$confarray=$this->config_array;
		foreach($config as $conf) {
			$confarray=$confarray[$conf];
		}
		if($confarray) {
		  return $confarray; 
		}
		return false;
	}
	
	public function switch_environment($env) {
	  if(is_array($this->{$env})) {
	    return $this->inject_configuration($this->{$env});
	  }
	  return false;
	}
	
	/**
    *  @return array
    */
	
	function __get($value) { 
	  return $this->return_config($value);
	}
	
	
}

?>