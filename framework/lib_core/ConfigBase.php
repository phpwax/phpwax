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
class ConfigBase extends ApplicationBase
{
	
	private $config_array;
	private $environment;
	private $actions_array;
	private $behaviours_array;
	
	function __construct()
	{
	  $this->load_config();
		$db=$this->return_config('db');
	  $this->init_db($db);
		$this->create_behaviours();
	}
	
	
	/**
    *  Loads the config.yml file
    *  @return array      sets value of $this->config_array
    */
	private function load_config()
	{
	   $configFile=APP_DIR.'/config/config.yml';
	     try
	     {
	       if(is_file($configFile)){}
	       else throw new Exception("Missing Configuration file at -".APP_DIR.'config/config.yml');
	     }
	     catch(Exception $e) 
        {
         $this->process_exception($e);
        }
			$this->config_array = Spyc::YAMLLoad($configFile);
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
        }
      catch(Exception $e) 
        {
            $this->process_exception($e);
        }
	}
	
	
	/**
    *  Constructs a route from the url
    *  @return string      The Controller
    */
	protected function make_controller_route()
	{
	   $route_array=array_values(array_filter(explode("/", $_GET['route'])));
	   $tempController=$route_array[0];
	   $controllerDir=APP_DIR."/controller/";
	  try
	  {
   	   switch(TRUE)
   	   {
   	      case $this->check_controller($controllerDir.$tempController."_controller.php"):
   	      $controller=$tempController; 
   	      array_shift($route_array);
   	      $this->actions_array=$route_array;
   	      break;
      
   	      case isset($this->config_array['route'][$tempController]) && $this->check_controller($controllerDir.$this->config_array['route'][$tempController]."_controller.php"):
   	      $controller=$this->config_array['route'][$tempController]; 
   	      $this->actions_array=$route_array;
   	      break;
      
   	      case isset($this->config_array['route']['default']) && $this->check_controller($controllerDir.$this->config_array['route']['default']."_controller.php"):
   	      $controller=$this->config_array['route']['default']; 
   	      $this->actions_array=$route_array;
   	      break;
   	      
   	      case isset($this->config_array['route'][$tempController]);
   	      throw new Exception("Missing Controller - ".$this->config_array['route'][$tempController]); break;
      
   	      default: throw new Exception("Missing Controller - ".$tempController);      
   	   }
     }
     catch(Exception $e) 
     {
        $this->process_exception($e);
     }
     return $controller;
	}
	
	/**
    *  Checks whether a file exists for the named controller
    *  @return boolean      If file exists true
    */
	private function check_controller($file)
	{
	   if(is_file($file)) return true;
	   else return false;
	}
	
	/**
    *  Sets the value of the action - route minus the controller
    *  @return array      remaining actions
    */
	protected function read_actions()
	{
	   return $this->actions_array;
	}
	
	
	
	public function return_config($config)
	{
		if($this->config_array[$config]) { return $this->config_array[$config]; }
		else {return false;}
	}
	
	//create js file holding all event-selectors behaviour functions.
	private function create_behaviours() {
		
		$behaviours_array=array();
		$behavioursFile=APP_DIR.'config/behaviours.yml';
		$destination=APP_DIR.'public/javascript/lib/app.event-selectors.js';
		$behaviours_array = Spyc::YAMLLoad($behavioursFile);
		/*
		 * Built in behaviours
		 */
		$behaviours_array['.FMvalidate']=array(
			'submit'=>'return jsValidate(element);'
		);
		
		$num_behaviours=count($behaviours_array);
		$behaviour_count = 1;
		
		$js.= "var Rules = {\n";
		//loop each behaviour in behaviours.yml
		foreach ($behaviours_array as $element=>$value) {		
			$js .= "\n";
			//create an array of each of the styles to attach the event to.
			$styles = explode(",",$element);
			
			$js .= "	'";
				
			//find number of styles related to behaviour $element
			$num_styles = count ($styles);
			$style_count = 1;
				
			//find number of events related to each $element
			$num_events = count($value);
			$event_count = 1;
				
			//loop each of the styles for behaviour $element
			foreach ($styles as $style) {
				
				//loop each of events and attach each to $style
				foreach ($value as $event=>$action) {
								
					$js .= $style.":".$event;
						
					//insert a comma if more styles need to be printed.
					if ($style_count<$num_styles || $event_count<$num_events) {
						$js .= ", ";
					}
					$event_count++;
				}//end loop
				$style_count++;
				$event_count = 1;
			}//end loop
			$js .= "'";		
			
			//create the function to execute the event
			$js .= ": function(element, event) {\n";
			
			//insert each action into the function
			foreach ($value as $event=>$action) {
				$js .= "		".$action."\n";
			}//end loop
			
			$js .= "	}";
				
			//if more behaviours need printing, insert a comma
			if ($behaviour_count<$num_behaviours) {
				$js .= ",\n\n";
			}
			$behaviour_count++;
		}//end loop
		
		$js .= "\n\n}";
		
		//** Temporary fix for nested quote bug in spyc.php
	    $js=str_replace("*","'",$js);
	    //** Remove when resolved
		if(!$handle=fopen($destination, 'wb')) {throw new exception("Cannot Open behaviours file at (app/public/javascript/lib/app.event-selectors.js)");}
	  if(!$result=fwrite($handle, $js) ) {throw new exception("Cannot Write to behaviours file at (app/public/javascript/lib/app.event-selectors.js)");}
		
		
	}	
	
}

?>