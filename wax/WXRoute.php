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
	  $controllerDir=CONTROLLER_DIR;
   	switch(TRUE) {
 	  	case $this->check_controller($controllerDir.ucfirst($tempController)."Controller.php"):
 	    $controller=$tempController; 
 	    array_shift($route_array);
 	    $this->actions_array=$route_array;
 	    break;
      
 	    case isset($this->config_array['route'][$tempController]) && $this->check_controller($controllerDir.ucfirst($this->config_array['route'][$tempController])."Controller.php"):
 	    $controller=$this->config_array['route'][$tempController]; 
 	    $this->actions_array=$route_array;
 	    break;
      
 	    case isset($this->config_array['route']['default']) && $this->check_controller($controllerDir.ucfirst($this->config_array['route']['default'])."Controller.php"):
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