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
	}
	
	public function pick_controller() {
		while($this->route_array[1] && is_dir(CONTROLLER_DIR.$this->route_array[0])) {
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
		if(is_file(CONTROLLER_DIR.$class.".php")) return $class;
		if($maps = $this->check_controller_mapping($controller)) return $maps;
		throw new WXException("Missing Controller - ".$controller, "Controller Not Found");
	  return false;
	}
	
	private function check_controller_mapping($controller) {
		if($mapping = $this->config_array['route'][$controller]) {
			return $this->check_controller($mapping);
		} elseif($mapping = $this->config_array['route']['default']) {
		  array_unshift($this->route_array, $this->config_array['route']['default']);
			return $this->check_controller($mapping);
		}
		return false;
	}
	
	public function read_actions() {
		$this->actions_array = $this->route_array; 
		array_shift($this->actions_array);
	  return $this->actions_array;
	}
	
	
	
} // END class 

?>