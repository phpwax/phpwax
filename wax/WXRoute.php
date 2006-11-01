<?php
/**
 * 
 *
 * @author Ross Riley
 * @version 0.6
 * @package wx.php.core
 **/

/**
 * Route construction class
 *
 * @package wx.php.core
 * @author Ross Riley
 * 
 * This class fetches the URL parameters from $_GET
 * It also requires access to the config object to check configurations.
 **/
class WXRoute extends ApplicationBase
{
	protected $route_array=array();
	protected $config_array=array();
	protected $actions_array=array();
	
	public function __construct() {
		$this->route_array=array_values(array_filter(explode("/", $_GET['route'])));
		$conf=new WXConfigBase;
		$this->config_array=$conf->return_config("all");		
		$this->make_controller_route();
	}
	
	
	/**
    *  Constructs a route from the url
    *  @return string      The Controller
    */
	public function make_controller_route() {
	  $route_array=$this->route_array;
	  $tempController=$route_array[0];
		if(is_dir(CONTROLLER_DIR.$route_array[0])) {
			if(!$route_array[1]) { $route_array[1] = "page"; }
			$tempController = $route_array[1];
		} 
   	switch(TRUE) {
			case is_dir(CONTROLLER_DIR.$route_array[0]) && 
				$this->check_controller(CONTROLLER_DIR.$route_array[0]."/".ucfirst($route_array[0]).ucfirst($tempController)."Controller.php"):
				$controller=$route_array[0]."/".$tempController;
				array_shift($route_array);
				array_shift($route_array);
			break;
 	  	case $this->check_controller(CONTROLLER_DIR.ucfirst($tempController)."Controller.php"):
 	    	$controller=$tempController; 
 	    	array_shift($route_array);
 	    	$this->actions_array=$route_array;
 	    break;
      
 	    case isset($this->config_array['route'][$tempController]) && $this->check_controller(CONTROLLER_DIR.ucfirst($this->config_array['route'][$tempController])."Controller.php"):
 	    	$controller=$this->config_array['route'][$tempController]; 
 	    	$this->actions_array=$route_array;
 	    break;
      
 	    case isset($this->config_array['route']['default']) && $this->check_controller(CONTROLLER_DIR.ucfirst($this->config_array['route']['default'])."Controller.php"):
 	    	$controller=$this->config_array['route']['default']; 
 	    	$this->actions_array=$route_array;
 	    break;
        
   	  default: throw new WXException("Missing Controller - ".$tempController, "Controller Not Found");
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
	
	public function read_actions()
	{
	   return $this->actions_array;
	}
	
	
	
} // END class 

?>