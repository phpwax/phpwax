<?php
namespace Wax\Config;

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
class Config
{
	
	static $config_array = array();
	static $app_yaml_file=false;
	static $initialised = false;
	
	static public function initialise($initial_config=false) {
	  if(self::$initialised) return true;
	  switch(true) {
	    case is_readable(CONFIG_DIR."config.yml"): self::init_yaml(); break;
	    case is_readable(CONFIG_DIR."config.php"): self::init_php(); break;
	  }
		self::$initialised=true;
	}
	
	static public function init_yaml() {
	  if(!$initial_config) $initial_config = CONFIG_DIR."config.yml";
	  self::$app_yaml_file = $initial_config;
	  self::$config_array = self::load_yaml(self::$app_yaml_file);
	  
	}
	
	static public function init_php() {
	  include( CONFIG_DIR."config.php" );
	}
	
	/**
    *  Loads any .yml file
    *  @return array
    */
	static private function load_yaml($config_file) {	
		if(is_readable($config_file)){
		  if(function_exists("syck_load")) {
		    return syck_load(file_get_contents($config_file));
		  }
		  else return Spyc::YAMLLoad($config_file);
	  } else {
		  return false;
    }	
	}
	
	/**
	 * Loads a file from the config directory.
	 * If path is absolute then load it in directly.
	 * Detects readability and uses YAML or php where appropriate.
	 *
	 * @return void
	 **/
	static public function load($config_file, $as=false) {
	  if(strpos($config_file,"/")===FALSE) $config_file = CONFIG_DIR.$config_file;
	  if(substr($config_file, -3)=="yml") return self::load_yaml($config_file);
	  if(substr($config_file, -3)=="php") return self::set(include($config_file));
	  if(substr($config_file, -3)=="ini") {
	    $res = ConfigINI::parse($config_file, true);
	    if($as) Config::set($as, $res);
	    else Config::set($res);
    }
	}
	
	
	/**
    *  The clever function. Returns the configuration array for the particular 
    *  portion of the file you want - or if you specify 'all' as the parameter, the whole array.
    *  @return array
    */
	
	static public function return_config($config=null) {
		if($config=="all") return self::$config_array;
		if(strpos($config, ".") !==false) $config=explode(".", $config);
		else $config=explode("/", $config);

		$confarray=self::$config_array;
		foreach($config as $conf) {
  		if(!$confarray) return false;
			if(array_key_exists($conf,$confarray)) $confarray=$confarray[$conf];
			else $confarray=false;
		}
		if($confarray) {
		  return $confarray; 
		}
		return false;
	}
	
	static public function replace_yaml($file) {
	  self::$config_array = self::load_yaml($file);
	}
	
	/**
    *  Allows you to change the configuration on the fly. Use either a file or PHP array.
    *  @return bool
    */
	
	static public function set($new_config, $new_value=false) {    
	  //self::initialise();
	  if(!is_array($new_config)) {
	    if(strpos($new_config,".")!==false) {
	      $names = explode('.', $new_config);
	      $new_config = array();
        switch (count($names)) {
          case 2: $new_config[$names[0]][$names[1]] = $new_value; break;
        	case 3: $new_config[$names[0]][$names[1]][$names[2]] = $new_value; break;
        	default: $new_config[$names[0]][$names[1]][$names[2]][$names[3]] = $new_value; break;
        }
	    } else $new_config = array($new_config=>$new_value);
	  }
	  self::$config_array = array_merge_recursive(self::$config_array, $new_config);
	}
	
	/**
    *  An environment is a sub-array of the configuration, this simply copies the environment array
    *  to the root of the configuration overwriting anything that gets in its way.
    *  @return bool
    */
	
	static public function set_environment($env) {
	  self::initialise();
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
	  self::initialise();
	  return self::return_config($value);
	}
	

		
	
}

