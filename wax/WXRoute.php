<?php
/**
 * 
 *
 * @author Ross Riley
 * @package PHP-Wax
 **/

/**
 * Route construction class
 *
 * @package PHP-Wax
 * @author Ross Riley
 * 
 * This class fetches the URL parameters from $_GET
 * It also requires access to the config object to check configurations.
 **/
class WXRoute {
  protected static $url = array();
	protected $route_array=array();
	protected $config_array=array();
	protected $actions_array=array();
	protected $controller;
	public function __construct() {
		$this->route_array=array_values(array_filter(explode("/", $_GET['route'])));
		$route = $_GET['route'];
		unset($_GET['route']);
		$this->route_array = array_merge($this->route_array, $_GET);
		self::$url = $this->route_array;
		$_GET['route']=$route;
		$this->config_array=WXConfiguration::get('route');
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
	  $this->detect_maintenance();
	  if(empty($this->route_array)) $this->route_array[0]=$this->config_array['default'];
		if(isset($this->config_array[$this->route_array[0]])) {
			$this->route_array[0]=$this->config_array[$this->route_array[0]];
		}
		$this->map_urls();
	}
	
	
	public function pick_controller() {
	  if(is_dir(CONTROLLER_DIR.$this->route_array[0])) {
    	$this->route_array[1]=$this->route_array[0]."/".$this->route_array[1]."/";
    	array_shift($this->route_array);
    }
	  if($res = $this->check_controller($this->route_array[0])) return $res;
	}
	
	protected function detect_maintenance() {
	  $maintenance = WXConfiguration::get("maintenance");
	  if($maintenance['ip'] && $maintenance['redirect']) {
	    if($_SERVER['REMOTE_ADDR']==$maintenance['ip']) return false;
	    if($this->route_array[0] != $maintenance['redirect']) $this->route_array[0]=$maintenance['redirect'];
	    else return false;
	    return true;
	  }
	  return false;
	}
	
	/**
    *  Checks whether a file exists for the named controller
    *  @return boolean      If file exists true
    */
	protected function check_controller($controller) {
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
	
	public static function get_url_val($val) {
	  return self::$url[$val];
	}
	
	public function get_url() {
	  return self::$url;
	}
	
	public function map_urls() {
	  foreach($this->config_array as $k=>$v) {
	    if($k=="default") continue;
	    $patterns = explode("/", $k);
	    $replace = explode("/", $v);
	    if($this->is_match($patterns)) {
	      foreach($patterns as $i=>$val) {
  	      if($val!="*" && $val!=$this->route_array[$i]) continue;
  	      self::$url[$replace[$i]]=$this->route_array[$i];
  	    }
  	    $matched = true;
  	  }
  	  if($matched) break;
	  }
	  return $this->get_url();
	}
	
	protected function is_match($pattern) {
	  $url = $this->route_array;
	  if($url[0] != $this->get_url_controller()) array_unshift($url, $this->get_url_controller());
	  $match = true;
	  foreach($pattern as $k=>$val) {
	    if($val != $this->route_array[$k] && $val !="*") $match=false;
	  }
	  return $match;
	}
	
	public function is_default($controller) {
	  if($this->config_array["default"]==$controller) return true;
	  return false;
	}
	
	public function build_url($params) {
	  if(!$params["controller"]) $url = $this->unset_numeric($this->map_urls());
	  else $url=array();
	  return $url;
	}
	
	public function build_params($array) {
	  if(!count($extra_params)) {
		  if(array_pop(array_values($url))=="index") array_pop($url);
    	return str_replace("//","/",$url_base.implode("/", $url)."/");
		}
    return $url_base . str_replace("//", "/", implode("/", $url)."/"). "?".http_build_query($extra_params, "", "&");
	}
	
	
	public function unset_numeric($array) {
	  foreach($array as $k=>$v) if(!is_numeric($k)) $arr[$k]=$v;
	  return $arr;
	}
	
	
	
}

?>