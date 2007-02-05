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
  public $use_view="_default";
  protected $class_name='';
  public $referrer;
	public $use_plugin=false;
	public $shared_plugin=false;
	public $plugin_share = 'shared';
	public $filters = array(); 


	public function __construct() {
	  $this->class_name=get_class($this);
	  $this->set_referrer();
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
		if(substr($route, 0,1) != "/" && (!substr_count($route, "http")>0) ) {
		  $controller=new WXRoute;
		  $route = "/".$controller->get_url_controller()."/$route";
		}
		if(!strpos($route, "http")===0) {
		  $route = "http://".$_SERVER['HTTP_HOST'].$route;
	  }
  	header("Location:$route");
   	exit;
  }

	public function run_filters($when) {
		if(!is_array($this->filters[$when])) return false;
    foreach($this->filters[$when] as $action=>$filter) {
      if(is_array($filter) && $action=="all" && is_array($filter[1])) {
        foreach($filter[1] as $excluded_action) {
          if($excluded_action != $this->action) $this->$filter[0]();
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

	
	protected function param($param) {
	  if($param=="id") return $this->route_array[0];
	  if(isset($this->route_array[$param])) return $this->route_array[$param];
	  return false;
	}
	
	protected function set_referrer() {
	  Session::set('referrer', $_SERVER['HTTP_REFERER']);
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
		if(!$this->use_view) return false;
		if($this->use_view == "none") return false;
		if($this->use_view=="_default") $this->use_view = $this->action;
    $view = new WXTemplate($this);
    $view->add_path(VIEW_DIR.$this->controller."/".$this->use_view);
    $view->add_path(PLUGIN_DIR.$this->use_plugin."/view/".get_parent_class($this)."/".$this->use_view);
    $view->add_path(PLUGIN_DIR.$this->use_plugin."/view/".$this->plugin_share."/".$this->use_view);
    $view->add_path(PLUGIN_DIR.$this->share_plugin."/view/".get_parent_class($this)."/".$this->use_view);
    $view->add_path(PLUGIN_DIR.$this->share_plugin."/view/".$this->plugin_share."/".$this->use_view);
    ob_end_clean();		
    return $view->parse();
  }
  
  /**
   *  Returns a layout as a string.
	 *	@return string
 	 */
  protected function render_layout() {
		if(!$this->use_layout) return false;
    $layout = new WXTemplate($this);
    $layout->add_path(VIEW_DIR."layouts/".$this->use_layout);
    $layout->add_path(PLUGIN_DIR.$this->use_plugin."/view/layouts/".$this->use_layout);
    $layout->add_path(PLUGIN_DIR.$this->share_plugin."/view/layouts/".$this->use_layout);
    ob_end_clean();
    return $layout->parse();
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
	    $path = $path."_".$partial;
	  } else {
	    $partial = $path;
	    $path = "_".$path;
	  }
	  if($this->is_public_method($this, $partial."_partial()")) $this->{$partial."_partial()"};
	  $partial = new WXTemplate($this);
    $partial->add_path(VIEW_DIR.$path);
    $partial->add_path(VIEW_DIR.$this->controller."/".$path);
    $partial->add_path(PLUGIN_DIR.$this->use_plugin."/view/".get_parent_class($this)."/".$path);
    $partial->add_path(PLUGIN_DIR.$this->use_plugin."/view/".$this->plugin_share."/".$path);
    $partial->add_path(PLUGIN_DIR.$this->share_plugin."/view/".get_parent_class($this)."/".$path);
    $partial->add_path(PLUGIN_DIR.$this->share_plugin."/view/".$this->plugin_share."/".$path);
    return $partial->parse();
	}
	
	/**
   *  Returns a single path as a string.
	 *	@return string
 	 */
	public function view_to_string($view_path, $values=array(), $suffix="html") {
		$view= new WXTemplate($values);
		$view->add_path(VIEW_DIR.$view_path);
		return $view->parse($suffix);
	}

	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions prior to running the action.
	 *	@access protected
 	 */
   protected function controller_global() {}


	/**
 	 *	This method is what it's all about, it simply steps through the filters and
	 *	runs the action.
	 *
	 *	It then picks up the view content along the way and hey presto, you have a page.
	 *  If you've messed up and not provided an action, it throws an exception.
	 *
	 *	@access protected
 	 */
	public function execute_request() {
		$route = new WXRoute;
	  $this->route_array = $route->read_actions();
	  $this->controller = $route->get_url_controller();
	  if(!$this->action = $this->route_array[0]) {
	    $this->action = "index";
	  }
	  array_shift($this->route_array);
	  $this->controller_global();
	  $this->run_filters("before");
	  if(!$this->is_public_method($this, $this->action)) {
	    if($this->is_public_method($this, WXInflections::underscore($this->action))) {
	      $underscore_action = WXInflections::underscore($this->action);
	      $this->{$underscore_action}();
	    } elseif(method_exists($this, 'method_missing')) {
			  $this->missing_action();
		  } else {  	    
		    $class=get_class($this);
			  throw new WXRoutingException("No Public Action Defined for - ".$this->action." in controller {$class}.", "Missing Action");
			  exit;
  		}
		} else $this->{$this->action}();
		$this->run_filters("after");		
		$this->content_for_layout = $this->render_view();
		if($content = $this->render_layout()) echo $content;
		elseif($this->content_for_layout) echo $this->content_for_layout;
		else echo "";
	}

   
}

?>
