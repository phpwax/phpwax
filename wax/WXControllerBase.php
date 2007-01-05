<?php
/**
	* @package php-wax
	*/

/**
 * @package php-wax
 * Provides basic functionality which controllers inherit.
 */

abstract class WXControllerBase
{

  protected $route_array=null;
  public $controller;
  public $action;
  public $use_layout='application';
  public $use_view=null;
  private $class_name='';
  public $referrer;
	public $use_plugin=false;
	public $plugin_share = 'shared';
	
	/**
	 *	An array of filters that can run actions before or after other actions.
	 *  Takes the form array("action"=>array("before", "check_authorise"));
	 *	@access protected
	 *	@var 		array
	 */
	public $filters = array(); 


   
  /** Set to 0 by default this decides whether any further
   * 	route information is passed on to the action.
   * 	This is then overridden in the controller by setting
   * 	$this->accept_routes to > 0, then the action can decide
   * 	what to do based on the $route_array
   */
   public $accept_routes=0;
  
	function __construct() {            
	  $this->class_name=get_class($this);
    $this->referrer=Session::get('referrer');
    $this->filters["before"]=array();
    $this->filters["after"]=array();
  }

	/**
 	 *	Sends a header redirect, moving the app to a new url.
	 *	@access protected
	 *	@param string $route
 	 */   
	public function redirect_to($route) {
		if(substr($route, 0,1) != "/") {
		  $controller=new WXRoute;
		  $route = "/".$controller->get_url_controller()."/$route";
		}
		$this->set_referrer();
		$route = "http://".$_SERVER['HTTP_HOST'].$route;
  	header("Location:$route");
   	exit;
  }
  
  public function run_before_filters() {
    if(!is_array($this->filters["before"])) return false;
    foreach($this->filters["before"] as $action=>$filter) {
      if(is_array($filter) && $action=="all") {
        foreach($filter[1] as $excluded_action) {
          if($excluded_action != $this->action) $this->{$filter[0]}();
        }
      }
      elseif($action == $this->action || $action == "all") {
          $this->$filter();
      }
    }
  }
  
  public function run_after_filters() {
    if(!is_array($this->filters["after"])) return false;
    foreach($this->filters["after"] as $action=>$filter) {
      if(is_array($filter) && $action=="all" && is_array($filter[1])) {
        foreach($filter[1] as $excluded_action) {
          if($excluded_action != $this->action) $this->$filter[0];
        }
      }
      elseif($action == $this->action || $action == "all") {
          $this->$filter();
      }
    }
  }
  
  public function before_filter($action, $action_to_run, $except=null) {
    if($except) {
			$this->filters["before"][$action]=array($action_to_run, $except);
			return true;
		}
    $this->filters["before"][$action]=$action_to_run;
  }
  
  public function after_filter($action, $action_to_run, $except=null) {
    if($except) {
			$this->filters["after"][$action]=array($action_to_run, $except);
			return true;
		}
    $this->filters["after"][$action]=$action_to_run;
  }


	
	
	public function param($param) {
	  if($param=="id") return $this->route_array[0];
	  if(isset($this->route_array[$param])) return $this->route_array[$param];
	  return false;
	}
	
	protected function set_referrer() {
	  if($_GET['route']  == '/index') Session::set('referrer', $_GET['route']);
		else Session::set('referrer', "/".$_GET['route']);
	}
	
	protected function process_controller() {
	  $route = new WXRoute;
	  $this->route_array = $route->read_actions;
	  $this->controller = $route->get_url_controller();
	  if(!$action = $this->route_array[0]) {
	    $action = "index";
	  }
	  $this->controller_global();
	  if(!$this->is_public_method($this, $action)) {
	    if(method_exists($this, 'missing_action')) {
			  $this->missing_action();
		  } else {
			  throw new WXRoutingException("No Public Action Defined for - ".$this->action." in controller {$this->class_name}.", "Missing Action");
			  exit;
  		}
		}
	  $this->run_before_filters();
		$this->{$this->action}();
		$this->run_after_filters();
		$this->content_for_layout = $this->render_view;
		echo $this->render_layout;
	}
	
	
	/**
   *  Surely it's self-documenting?.
	 *	@return bool
 	 */
	protected function is_public_method($object, $method) {
    if(!method_exists($object, $method)) return false;
    $this_method = new ReflectionMethod($object, $method);
		if($this_method->isPublic()) return true;
	  return false;
  }
  
  
  /**
   *  Returns a view as a string.
	 *	@return string
 	 */
  protected function render_view() {
    $view = new WXTemplate($this);
    $template->add_path(VIEW_DIR.$this->controller.$this->action);
    $template->add_path(PLUGIN_DIR.$this->use_plugin."/view/".get_parent_class($this).$this->action);
    $template->add_path(PLUGIN_DIR.$this->use_plugin."/view/".$this->plugin_share."/".$this->action);
    return $view->parse;
  }
  
  /**
   *  Returns a layout as a string.
	 *	@return string
 	 */
  protected function render_layout() {
    $layout = new WXTemplate($this);
    $layout->add_path(VIEW_DIR."layouts/".$this->use_layout);
    $layout->add_path(PLUGIN_DIR.$this->use_plugin."/view/layouts/".$this->use_layout);
    return $layout->parse;
  }
  
  
  /**
   *  Returns a partial as a string.
   *  If it exists this method will also run a custom method to initialise
	 *	@return string
 	 */
  public function render_partial($path) {
	  if(strpos($path, "/")) {
	    $partial = substr($path, strrpos($path, "/")+1);
	    $path = substr($path, 0, strrpos($path, "/")+1);
	    $path = $path.$partial;
	  } else {
	    $partial = $path;
	    $path = "_".$path;
	  }
	  if($this->is_public_method($this, $partial)) $this->{$partial."_partial()"};
	  $partial = new WXTemplate($this);
    $partial->add_path(VIEW_DIR.$path);
    $partial->add_path(VIEW_DIR.$this->controller.$path);
    $partial->add_path(PLUGIN_DIR.$this->use_plugin."/view/".get_parent_class($this).$path);
    $partial->add_path(PLUGIN_DIR.$this->use_plugin."/view/".$this->plugin_share."/".$path);
    return $partial->parse;
	}
	
	/**
   *  Returns a single path as a string.
	 *	@return string
 	 */
	public function view_to_string($view_path, $values=array()) {
		$view= new WXTemplate($values);
		$view->add_path(VIEW_DIR.$view_path);
		return $view->parse();
	}

	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions prior to running the action.
	 *	@access protected
 	 */
   public function controller_global() {}

   
}

?>
