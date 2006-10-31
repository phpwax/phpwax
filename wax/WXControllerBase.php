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
  protected $referrer;
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
  }

	/**
 	 *	Sends a header redirect, moving the app to a new url.
	 *	@access protected
	 *	@param string $route
 	 */   
	public function redirect_to($route) {
  	header("Location:$route");
   	exit;
  }
  
  public function run_before_filters() {
    foreach($this->filters as $key=>$filter) {
      if($key == $this->action || $key == "all") {
        if($filter[0]=="before") {
					if($filter[2] && is_array($filter[2])) {
							foreach($filter[2] as $excluded_filter) {
								if($this->action !=$excluded_filter) {
									$filter = $filter[1];
									$this->$filter();
								}
							}
					} else {
          	$filter = $filter[1];
          	$this->$filter();
					}
        }
      }
    }
  }
  
  public function run_after_filters() {
    foreach($this->filters as $key=>$filter) {
      if($key == $this->action || $key == "all") {
        if($filter[0]=="after") {
          $filter = $filter[1];
          $this->$filter();
        }
      }
    }
  }
  
  public function before_filter($action, $action_to_run, $except=null) {
    $this->filters[$action]=array("before", $action_to_run);
		if($except) {
			$this->filters[$action][2]=$except;
		}
  }
  
  public function after_filter($action, $action_to_run, $except=null) {
    $this->filters[$action]=array("after", $action_to_run);
		if($except) {
			$this->filters[$action][2]=$except;
		}
  }
  

	/**
 	 *	Allows overriding of the default routes.
	 *	@access protected
	 *	@param array $route_array
 	 */
  protected function set_routes($route_array) {
   	$this->route_array=$route_array;
  }

	/**
 	 *	Allows overriding of the default action.
	 *	@access protected
	 *	@param string $action
 	 */
  protected function set_action($action) {
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
	protected function view_to_string($view_path, $values=array()) {
  	$view_html='';
    if(!$controller_name) { 
			$controller_name=substr( $this->class_name,0,strpos($this->class_name,"_")); 
		}
		$view= new WXTemplate("preserve");
		foreach($values as $k=>$v) {
	  	$view->$k=$v;
	  }
		if($view_html=$view->parse($view_path.".html") ) {  
   		return $view_html;
		} else {
			throw new WXException("Couldn't find file ".$controller_name."/".$view_name.".html", "Missing Template");
		}
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
