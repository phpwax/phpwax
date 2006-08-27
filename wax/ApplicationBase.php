<?php
/**
 * 	@package wx.php.core
 */

/**
	* 	@package wx.php.core
  *   The absolute base class. This will instantiate all of the other 
  *   four base classes, ConfigBase, ModelBase, ControllerBase, ViewBase.
  *
  *   Methods Available to sub-classes.....
  *   fetch_config()
  *   process_exception()
  *   process_error()
  *   inspect()
  */
class ApplicationBase
{
/**
	*	 Stores a reference to the config object
	*  @access private
	*  @var reference
	*/
	private 	$config_object=null;
/**
	*		Stores the name of the controller to be run.
	*		@access protected
	*		@var 		string
	*/
	protected $controller;
	/**
	 *	Stores an array of actions.
	 *	Initially this is all sections of the url
	 *	As the controller and action are loaded this is
	 *	then reduced to the remaining part of the url
	 *	@access protected
	 *	@var 		array
	 */
	protected $actions=array();
	/**
	 *	Stores the name of the action to be run.
	 *	@access protected
	 *	@var 		string
	 */
	protected $action;
	/**
	 *	Stores a reference to the controller object
	 *	This is then used to run the action within the controller.
	 *	@access private
	 *	@var reference
	 */
	protected $controller_object;
	/**
	 *	Stores a string containing the processed HTML for the view.
	 *	@access protected
	 *	@var 		string
	 */
	protected $view_html;
	/**
	 *	Stores a string containing the processed HTML for the layout.
	 *	@access protected
	 *	@var 		string
	 */
	protected $layout_html;
	public static $current_controller_name=null;
	public static $current_controller_path=null;
	public static $current_controller_object=null;
	
	
	/**
	 *	Sets up the application and orchestrates progression.
	 *  @access public
   *  @return void
   */
	function __construct()
	{
    set_exception_handler(array($this, 'process_exception'));    
    set_error_handler(array($this, 'process_error'), 259);
		$this->load_config();
		Session::start();
    // Clean User Input
    $filter=new InputFilter(array(), array(), 1,1);
    $_POST=$filter->process($_POST);
    $_GET=$filter->process($_GET);
		//$this->validation_intercept();
    $this->controller_object=$this->load_controller();
		self::$current_controller_object = $this->controller_object;
		self::$current_controller_name = get_class($this);			
    $this->create_page($this->controller_object);
  }

	/**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function load_config() 
	{
		if(!$this->config_object) { $this->config_object=new ConfigBase; }
		$route=new WXroute;		
		$this->controller=$route->make_controller_route();		
		$this->actions=$route->read_actions();		
    }
	
	/**
	 *	Looks up a configuration value from the loaded
	 *	config object. Returns array of values.
	 *  @access protected
   *  @return array
   */
	protected function fetch_config($config)
	{
		$this->load_config();
		if($this->config_object)
		{
		return $this->config_object->return_config($config);	
		} else {
			return false;
		}
	}
		
	/**
	 *	Maps the controller to the controller file.
	 *	Decides on the action to run: Either the named action
	 *	if passed, an 'index' action or finally a fallback action
	 *	named 'missing_action'.
	 *	Returns a reference to the controller object.
	 *  @access private
   *  @return obj
   */	
	private function load_controller()
	{
	  if(class_exists($this->controller."_controller", false)) { 		
			$controller=$this->controller."_controller";
		} else {
			$controller=ucfirst($this->controller)."Controller";
		}
	  $this->action=$this->actions[0];
	  array_shift($this->actions);
	  $final_route=$this->actions;
	  if(strlen($this->action)<1) { $this->action="index"; }
	  try
	  	{
	    	$cnt=new $controller();
	      $cnt->set_routes($final_route);
	      $cnt->set_action($this->action);
	      $cnt->controller_global();
	     }
	   catch(Exception $e) 
        {
            $this->process_exception($e);
        }
		$cnt->before_action($cnt->action);
		$cnt->{$cnt->action}();
		$cnt->filter_routes();
		$cnt->after_action($cnt->action);
		return $cnt;   
	}

	
	/**
	 *	Constructs the Output.
	 *  Uses the WXTemplate Class to set variables in view
	 *  @access private
   *  @return void
   */	
	private function create_page($cnt) {
		$tpl=new WXTemplate;		
		$tpl->urlid=$cnt->action;
    foreach(get_object_vars($cnt) as $var=>$val) {
      $tpl->{$var}=$val;
    }

		if(!$cnt->use_view) { 
			$use_view=$this->action; 
		} else {
			$use_view=$cnt->use_view;
		}
		if(strpos($use_view, '/')) { 
			$view_path="{$use_view}.html"; 
		} else { 
			$view_path=$this->controller."/".$use_view.".html"; 
		}
		$tpl->view_path=$view_path;
    if($cnt->use_layout) {
			$tpl->layout_path="layouts/".$cnt->use_layout.".html/layout";
	  	$tpl->setTemplate(FRAMEWORK_DIR.'lib_core/page_head.html');
    } else {
			$tpl->setTemplate(FRAMEWORK_DIR.'lib_core/empty_page.html');
		}
    try {
			$page_output=$tpl->execute();
      echo $page_output;
			if($_GET['route']  == '/index') {
				Session::set('referrer', $_GET['route']);
			} else {
				Session::set('referrer', "/".$_GET['route']);
			}	
 		} catch(Exception $e) {
        $this->process_exception($e);
    }
	}
	
	/**
	 *	The production environment exception handler.
	 *	Emails the trace and prints a generic user message to the screen. 
	 *  @access public
   *  @return void
   */	
	public function process_exception_prod($e)
	{
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
		$trace.="<h1>Application Error</h1>\n";
		$trace.="Oops, looks like there's been an error. Give it a few minutes and try again.";
		$trace.="</font>\n";
		echo $trace;
		$message=strip_tags($this->get_trace($e));
		error_log($message);
		//mail("ross@webxpress.com", "Application Error on production server", $message);
		if($this->fetch_config("debug")) { echo $message; }
		exit();
	}
	/**
	 *	The development environment exception handler.
	 *	Prints the stack trace to the screen. 
	 *  @access public
   *  @return void
   */	
	public function process_exception($e)
	{
		if($this->fetch_config('environment')=='production') {
	    	$this->process_exception_prod($e); exit;
		}
		echo $this->get_trace($e);        
		exit();
	}
	/**
	 *	Converts the stack trace into a single html string.
	 *  @access public
   *  @return string
   */	
	public function get_trace($e) {
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
    	$trace.="<h1>Application Error</h1>\n";
    	$trace.="<p>{$e->getMessage()}</p>\n";
    	$trace.="<pre style=\"background-color: #eee;padding:10px;font-size: 11px;\">";
    	$trace.="<code>{$e->getTraceAsString()}</code></pre>\n";
    	$trace.="<pre style=\"background-color: #eee;padding:10px;font-size: 11px; margin-top:5px;\">";
    	$trace.="<code>{$e->getFile()}\nLine: {$e->getLine()}</code></pre>\n";
    	$trace.="</font>\n";
		return $trace;
	}
	
	/**
	 *	Maps errors to the standard exception handler.
	 *  @access public
   *  @return void
   */	
	 public function process_error($errno, $errstr, $errfile, $errline) 
   {
     throw new Exception($errstr, $errno);
   }

	/**
	 *	Echos a formatted array to screen.
	 *  @access protected
   *  @return void
   */	
	public function inspect($array)
	{
	   echo "<pre>"; print_r($array); echo "</pre>"; 
	}
	
	


	

}


?>
