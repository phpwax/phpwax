<?php
/**
 * 	@package wx.php.core
 */

/**
	* 	@package wx.php.core
  *   The absolute base class. This will instantiate all of the other 
  *   four base classes, WXConfigBase, WXActiveRecord, WXControllerBase, WXTemplate.
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
	
	/**
	 *	Sets up the application and orchestrates progression.
	 *  @access public
   *  @return void
   */
	function __construct() {
		$this->load_config();		
		Session::start();
    $filter=new InputFilter(array(), array(), 1,1);
    $_POST=$filter->process($_POST);
    $_GET=$filter->process($_GET);		
    $this->controller_object=$this->load_controller();
    $this->create_page($this->controller_object);
  }

 
	/**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function load_config() {
		if(!$this->config_object) { $this->config_object=new WXConfigBase; }
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
	protected function fetch_config($config) {
		$this->load_config();
		if($this->config_object) {
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
	private function load_controller() {
	  $controller=ucfirst($this->controller)."Controller";
	  $this->action=$this->actions[0];
	  array_shift($this->actions);
	  $final_route=$this->actions;
	  if(strlen($this->action)<1) { $this->action="index"; }
	  try {
	  	$cnt=new $controller();
			$cnt->controller = $this->controller;
	    $cnt->set_routes($final_route);
	    $cnt->set_action($this->action);
	    $cnt->controller_global();
	  } catch(WXException $e) {
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
  	$write_to_cache = false;
   	$cache_file   = $this->controller . "_" . $cnt->action;
  	$page_output = false;
  	/**
  	*  if the action has been selected to cache within the controller 
  	*  or the global all has been raised then pull data from the cache 
  	*/
  	if(in_array($cnt->action, $cnt->caches) || in_array("all", $cnt->caches) ) {
			if($this->fetch_config("cache_actions")) {
    		$write_to_cache = true;
    		$page_output  = WXCache::read_from_cache($cache_file);
			}
  	}
  		
  	//if there's no page_content then create it 	
  	if(!$page_output) {
  		$tpl=new WXTemplate;
			if($cnt->use_plugin) {
				$tpl->view_base = PLUGIN_DIR.$cnt->use_plugin."/view/";
			}
  		$tpl->urlid=$cnt->action;
      foreach(get_object_vars($cnt) as $var=>$val) {
        $tpl->{$var}=$val;
      }
  
  		if(!$cnt->use_view) { 
  			$use_view=$this->action; 
  		} else {
  			$use_view=$cnt->use_view;
  		}
  		if(strpos($use_view, '/')===0) { 
  			$view_path=substr("{$use_view}.html", 1); 
  		} else { 
  			$view_path=$this->controller."/".$use_view.".html"; 
  		}
  		$tpl->view_path=$view_path;

      if($cnt->use_layout) {
  			$tpl->layout_path="layouts/".$cnt->use_layout.".html";
      }
  		$page_output=$tpl->execute();
		}
	
    echo $page_output;
    
    /**
     *  if the cache has expired and either the all global 
     *  cache or this action has been set to cache then write
     *  the result to the cache
     */    
    if($write_to_cache) {
      WXCache::write_to_cache($page_output, $cache_file); 
    }
    
		if($_GET['route']  == '/index') {
			Session::set('referrer', $_GET['route']);
		} else {
			Session::set('referrer', "/".$_GET['route']);
		}
				
	}
	

	
	/**
	 *	Maps errors to the standard exception handler.
	 *  @access public
   *  @return void
   */	
	 public function process_error($errno, $errstr, $errfile, $errline) {
     throw new WXException($errstr, $errno);
   }

	/**
	 *	Echos a formatted array to screen.
	 *  @access protected
   *  @return void
   */	
	public function inspect($array) {
		echo "<pre>"; print_r($array); echo "</pre>"; 
	}
	
	

	

	

}


?>
