<?php
/**
 * 	@package wax.php.core
 */

/**
 *
 * @package wax.php.core
 *
 *  One of four base classes. Loaded by the application class
 *  and used to set application variables.
 *  
 *  Looks in app/config directory to load config.yml
 *  Also finds behaviours.yml to load javascript behaviours.
 *
 *  Main Tasks are as follows......
 *    1. Load database connection from config file
 *    2. Construct url routes based on overrides in config file
 *    3. Setup selected environment - development, production or test
 *
 *  @author Ross Riley
 *
 */
class WXConfigBase
{
	
	private $config_array;
	private $environment;
	private $actions_array;
	private $behaviours_array;
	private $cachedest;
	private $cache_length = "10000";
	private $fromcache=false;
	static private $instance=false;
	
	function __construct() {
		if(self::$instance) {
			$this->cachedest='config_cache';			
	  	$this->load_config();
			if(!WXActiveRecord::getDefaultPDO()) {
				$db=$this->return_config('db');
				define ('ENV', $this->return_config("environment"));
	  		$this->init_db($db);	
			}			
		}
	}
	
	static public function set_instance() {
		if(!self::$instance) {
			self::$instance=new WXConfigBase();
		}
	}
	
	
	/**
    *  Loads the config.yml file
    *  @return array      sets value of $this->config_array
    */
	private function load_config() {	
		if($this->return_config("cache_config") && $cache_out = WXCache::read_from_cache($this->cachedest) ) {
			$this->config_array = unserialize($cache_out);
		} else { 
	  	$configFile=APP_DIR.'/config/config.yml';
	    if(is_readable($configFile)){
				$this->config_array = Spyc::YAMLLoad($configFile);
				$this->config_array=$this->merge_environments($this->config_array);		
				} else {
				throw new WXException("Missing Configuration file at -".APP_DIR.'config/config.yml');
      }
		}	
	}
	
	public function merge_environments($config_array) {
		$environment=$config_array['environment'];
	  foreach($config_array['development'] as $key=>$value) {
	  	$config_array[$key]=$value;  
	  }
	
	  foreach($config_array[$environment] as $key=>$value) {
	  	if(is_array($value)) { $config_array[$key]=array_merge($config_array[$key], $value); }
			else { $config_array[$key]=$value; }
	  } 
    unset($config_array['development']);
    unset($config_array['test']);
    unset($config_array['production']);
		return $config_array;
	}
	
	/* Sets up the database connection
	 * 
	 */
	public function init_db($db) {
		if(isset($db['socket']) && strlen($db['socket'])>2) {
			$dsn="{$db['dbtype']}:unix_socket={$db['socket']};dbname={$db['database']}"; 
		} else {
			$dsn="{$db['dbtype']}:host={$db['host']};port={$db['port']};dbname={$db['database']}";
		}
		
		$pdo = new PDO( $dsn, $db['username'] , $db['password'] );
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if(! WXActiveRecord::setDefaultPDO($pdo) ) {
    	throw new WXException("Cannot Initialise DB", "Database Configuration Error");
    }

	}
	
	
	/**
    *  Sets the value of the action - route minus the controller
    *  @return array      remaining actions
    */
	
	public function return_config($config=null) {
		if($config=="all") {
			return $this->config_array;
		}
		$config=explode("/", $config);
		$confarray=$this->config_array;
		foreach($config as $conf) {
			$confarray=$confarray[$conf];
		}
		if($confarray) { 
			return $confarray; 
		} else {
		  return false;
		}
	}
	
	
	private function write_to_cache() {
		if($this->return_config("cache_config")) {
			return WXCache::write_to_cache(serialize($this->config_array), $this->cachedest, $this->cache_length);
		}
	}
	
	function __destruct() {
		if(!file_exists($this->cachedest)) {
			$this->write_to_cache();
		}
		if(is_writable($this->cachedest) && File::is_older_than($this->cachedest, 36000)) {
			$this->write_to_cache();
		}
	}
	
	
}

?>