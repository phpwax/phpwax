<?php
/**
 * 	@package wx.php.core
 */

/**
 *
 * @package wx.php.core
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
class ConfigBase
{
	
	private $config_array;
	private $environment;
	private $actions_array;
	private $behaviours_array;
	private $cachedest;
	static private $instance=false;
	
	function __construct()
	{
		if(self::$instance) {
			$this->cachedest="/tmp/".str_replace("/", "_", APP_DIR).'cache';
	  	$this->load_config();
			$db=$this->return_config('db');
	  	$this->init_db($db);	
		}
	}
	
	static public function set_instance() {
		if(!self::$instance) {
			self::$instance=new ConfigBase();
		}
	}
	
	
	/**
    *  Loads the config.yml file
    *  @return array      sets value of $this->config_array
    */
	private function load_config()
	{
		if(is_readable($this->cachedest)) {
			echo "found cache";
			$this->config_array = unserialize(file_get_contents($this->cachedest));
		} else { 
	  	$configFile=APP_DIR.'/config/config.yml';
	    try {
	    	if(is_file($configFile)){
					$this->config_array = Spyc::YAMLLoad($configFile);
				} else throw new Exception("Missing Configuration file at -".APP_DIR.'config/config.yml');
	    } catch(Exception $e) {
				echo $e;
      }
		}	
		$this->config_array=$this->merge_environments($this->config_array);		
	}
	
	public function merge_environments($config_array) {
		$environment=$config_array['environment'];
	   foreach($config_array['development'] as $key=>$value)
	       {
	        $config_array[$key]=$value;  
	       }
	
	   foreach($config_array[$environment] as $key=>$value)
	       {
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
	public function init_db($db)
	{
		if(isset($db['socket']))
		{$dsn="{$db['dbtype']}:unix_socket={$db['socket']};dbname={$db['database']}"; }
		else {
		$dsn="{$db['dbtype']}:host={$db['host']}; port={$db['port']};dbname={$db['database']}";
		
		}
	   try 
	     {
	     $adapter=$db['dbtype'];
	     $pdo = new PDO( $dsn, $db['username'] , $db['password'] );
	     ActiveRecordPDO::setDefaultPDO( $pdo );
	     ActiveRecordPDO::setDBAdapter($adapter);
			 WXActiveRecord::setDefaultPDO($pdo);
        }
      catch(Exception $e) 
        {
            throw new Exception("Cannot Initialise DB");
        }
	}
	
	
	/**
    *  Sets the value of the action - route minus the controller
    *  @return array      remaining actions
    */
	
	
	
	
	public function return_config($config=null)
	{
		$config=explode("/", $config);
		
		$confarray=$this->config_array;
		foreach($config as $conf) {
			$confarray=$confarray[$conf];
		}
		if($confarray) { return $confarray; }
		else return $this->config_array;
	}
	
	
	private function write_to_cache() {
		try {
  	 $fp1=fopen($this->cachedest, 'w+');
  	 $result=fwrite($fp1, serialize($this->config_array));
  	 fclose($fp1);
		} catch(Exception $e) {
    	echo "couldn't write to cache(".$this->cachedest.")<br />";
			//echo $e;
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