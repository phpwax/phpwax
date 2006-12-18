<?php
/**
	* @package wx.php.core
	*/

/**
 * @package wx.php.core
 * Provides basic functionality which controllers inherit.
 */
require_once "ApplicationBase.php";

abstract class WXControllerBase extends ApplicationBase
{
  protected $models=array();
  protected $route_array=null;
	protected $controller;
  protected $action;
  public $use_layout='application';
  public $use_view=null;
  private $class_name='';
  public $referrer;
	protected $use_plugin=false;
	
	/**
	 *	An array of filters that can run actions before or after other actions.
	 *  Takes the form array("action"=>array("before", "check_authorise"));
	 *	@access protected
	 *	@var 		array
	 */
	public $filters = array(); 
	
	/**
	 *	An array of actions that implement caching, or 'all' to cache entire model.
	 *	@access protected
	 *	@var 		array
	 */
	protected $caches=array();

   
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
  

	/**
 	 *	Allows overriding of the default routes.
	 *	@access protected
	 *	@param array $route_array
 	 */
  public function set_routes($route_array) {
   	$this->route_array=$route_array;
  }

	/**
 	 *	Allows overriding of the default action.
	 *	@access protected
	 *	@param string $action
 	 */
  public function set_action($action) {
   	$this->action=$action;
  }

	/**
 	 *	Renders the given view using WXTemplate and returns the html as a string.
	 *	@access protected
	 *	@param string $controller_name if not given defaults to current.
	 *	@param string $view_name
	 *	@param array $values Values to be passed to the template.
	 *	@return string
 	 */
	public function view_to_string($view_path, $values=array()) {
  	$view_html='';
		$view= new WXTemplate("preserve");
		foreach($values as $k=>$v) {
	  	$view->$k=$v;
	  }
		if($view_html=$view->parse($view_path.".html") ) {  
   		return $view_html;
		} else {
			throw new WXException("Couldn't find file ".$view_name.".html", "Missing Template");
		}
	}
	
	public function render_partial($path, $values=array()) {
	  $tpl=new WXTemplate;
		if($this->use_plugin) {
			$tpl->view_base = PLUGIN_DIR.$this->use_plugin."/view/";
			$tpl->shared_dir = PLUGIN_DIR.$this->use_plugin."/view/shared/";
		}
    foreach($this as $var=>$val) {
      $tpl->{$var}=$val;
    }
    if(strpos($path, "/")) {
      $partial = "_".substr(strrchr($path, "/"),1);
      $path = substr($path, 0,strrpos($path, "/"))."/";
      $view_path = $path.$partial.".html";
    } else {
      $partial = "_".$path;
      $path = "";
      $controller = slashify(str_replace("Controller", "", $this->controller));
  		$view_path = slashify($controller)."/".$partial.".html";
    }

		if($this->use_plugin) {
		  $tpl->plugin_view_path=get_parent_class($this)."/".$partial.".html";
		}
		
		$tpl->view_path=$view_path;
		return $tpl->execute();
	}
	
	public function require_route($level) {
	  $this->accept_routes = $level;
	  if($level==1 && $val = $this->param("id") ) return $val;
	  else {
	    $this->route_array["id"]=$this->route_array[0];
	    unset($this->route_array[0]);
	    return $this->route_array;
    }
	}
	
	public function param($param) {
	  if($param=="id") return $this->route_array[0];
	  if(isset($this->route_array[$param])) return $this->route_array[$param];
	  return false;
	}
	
	public function set_referrer() {
	  if($_GET['route']  == '/index') Session::set('referrer', $_GET['route']);
		else Session::set('referrer', "/".$_GET['route']);
	}

	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions prior to running the action.
	 *	@access protected
 	 */
   protected function controller_global() {}

	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions prior to running the action.
	 *	@access protected
 	 */
   protected function before_action($action) {}
	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions after execution.
	 *	@access protected
 	 */
	protected function after_action($action) {}

	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions after execution.
	 *	@access protected
 	 */
	protected function filter_routes() {}
	
	/**
	 * method overloading function
	 *
	 * @return void
	 **/	
	function __call($method, $args) {
		if(method_exists($this, 'missing_action')) {
			$this->missing_action(); exit;
			throw new WXException("No Action Defined for - ".$this->action, "Missing Action");
		}
		exit;
	}
   
}

?>
