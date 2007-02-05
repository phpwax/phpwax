<?php
/**
 * 
 *
 * @author Ross Riley
 * @package php-wax
 **/

/**
 * Route construction class
 *
 * @package php-wax
 * @author Ross Riley
 * 
 * This class fetches the URL parameters from $_GET
 * It also requires access to the config object to check configurations.
 **/
class WXRoute {
	protected $route_array=array();
	protected $config_array=array();
	protected $actions_array=array();
	protected $controller;
	public function __construct() {
		$this->route_array=array_values(array_filter(explode("/", $_GET['route'])));
		$route = $_GET['route'];
		unset($_GET['route']);
		$this->route_array = array_merge($this->route_array, $_GET);
		$_GET['route']=$route;
		$this->config_array=WXConfiguration::get('route');
		foreach($this->route_array as $key=>$value) {
		  $this->route_array[$key]=WXInflections::underscore($value);
		}
		$this->map_routes();
		$this->controller = $this->pick_controller();	
	}
	
	
	/**
    *  In the configuration file you can setup a section called 'route'
    *  this allows you to magically rewrite the request to anything you like. 
    *  
    *  The left hand side specifies a match, the right hand side is the new output.
    *  for example, - admin/login: page/login - will rewrite the url from the left to the right.
    *  Hell, if you fancy it you can even include the '*' wildcard. -admin/* : page/
    *
    *  @return void
    */
    
	public function map_routes() {
	  if(empty($this->route_array)) $this->route_array[0]=$this->config_array['default'];
		if(isset($this->config_array[$this->route_array[0]])) {
			$this->route_array[0]=$this->config_array[$this->route_array[0]];
		}
	}
	
	
	public function pick_controller() {
	  if(is_dir(CONTROLLER_DIR.$this->route_array[0])) {
    	$this->route_array[1]=$this->route_array[0]."/".$this->route_array[1]."/";
    	array_shift($this->route_array);
    }
	  if($res = $this->check_controller($this->route_array[0])) return $res;
	}
	
	/**
    *  Checks whether a file exists for the named controller
    *  @return boolean      If file exists true
    */
	private function check_controller($controller) {
		if(strpos($controller, "/")) {
			$path = substr($controller, 0, strpos($controller, "/")+1);
			$class = slashcamelize($controller, true)."Controller";
			if(is_file(CONTROLLER_DIR.$path.$class.".php")) return $class;
		}
		$class = ucfirst($controller)."Controller";
		$default = ucfirst($this->config_array['default']."Controller");
		if(is_file(CONTROLLER_DIR.$class.".php")) return $class;
		if(is_file(CONTROLLER_DIR.$default.".php")) {
		  array_unshift($this->route_array, $this->config_array['default']);
		  return $default;
	  }
		throw new WXException("Missing Controller - ".$class, "Controller Not Found");
	}
	
	/**
    *  Strips the controller from the route and returns an array of actions
    *  This is designed to be called from the delegate controller.
    *
    *  @return boolean      If file exists true
    */
	
	public function read_actions() {	
		$actions = $this->route_array;
		array_shift($actions);
		return $actions;
	}
	
	public function get_url_controller() {
	  return $this->controller_to_url($this->pick_controller());
	}
	
	public function controller_to_url($controller) {
		$url = str_replace("Controller", "", $controller);
		return slashify($url);
	}
	
	
	
} // END class 

?>