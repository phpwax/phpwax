<?php
/**
	* @package wx.php.core
	*/

/**
 * @package wx.php.core
 * Provides basic functionality which controllers inherit.
 */
abstract class ControllerBase extends ApplicationBase
{
  protected $models=array();
  protected $route_array=null;
  protected $action;
  public $use_layout='default';
  public $use_view=null;
  private $class_name='';
  protected $referrer;
	protected $user_messages=array();
	protected $user_errors=array();
	protected $show_errors=true;
	protected $show_messages=true;
	protected $helpers=array();
	public $body_js_files=array();
   
  /** Set to 0 by default this decides whether any further
   * 	route information is passed on to the action.
   * 	This is then overridden in the controller by setting
   * 	$this->accept_routes to > 0, then the action can decide
   * 	what to do based on the $route_array
   */
   public $accept_routes=0;
   /*
    * This array contains the info that is passed on to the layout.
    * public functions are provided to add variables to this, do not
    * set directly.
    */
   public $layout=array(
        'styles'=>    array(
                              
                           ),
        'scripts'=>   array(
                            //'/javascript/lib/prototype.js',
                           // '/javascript/lib/scriptaculous.js',
                            '/javascript/lib/form.behaviours.js',
						//	'/javascript/lib/event-selectors.js',
						//	'/javascript/lib/app.event-selectors.js'
							
                           ),
         'meta'=>     array()
   );
  
   function __construct()
   {      
      // Find all models and instantiate
      $this->class_name=get_class($this);
      $this->referrer=Session::get('referrer');
   }
   

	/**
 	 *	Adds a custom stylesheet to the layout. main.css is included by default.
	 *	@access protected
	 *	@param string $url the location of the file (relative path)
	 *	@param string $media defaults to all. Options include screen, handheld, print
 	 */   
   protected function add_stylesheet($url, $media='all')
   {
      $this->layout['styles'][]=array($url, $media);
      return true;
   }

	/**
 	 *	Adds additional javascript src tags to the layout.
	 *	@access protected
	 *	@param string $url the location of the javascript
 	 */
   protected function add_javascript($url)
   {
      $this->layout['scripts'][]=$url;
      return true;
   }

	protected function add_body_js($file) {
		$this->body_js_files[]=$file;
	}

	protected function load_prototype()
	{
		$this->add_javascript('/javascript/lib/prototype.js');
   	$this->add_javascript('/javascript/lib/scriptaculous.js');
	}

	/**
 	 *	Adds custom layout variables which are passed onto the PHPTAL template.
	 *	@access protected
	 *	@param string $name
	 *	@param mixed $value
 	 */

	/**
 	 *	Adds custom meta data which are passed onto the PHPTAL template.
	 *	@access protected
	 *	@param string $name
	 *	@param string $content
 	 */   
   protected function add_meta_content($name, $content)
   {
   $this->layout['meta'][$name]=$content; return true;   
   }

	/**
 	 *	Sends a header redirect, moving the app to a new url.
	 *	@access protected
	 *	@param string $route
 	 */   
   public function redirect_to($route)
   {
   	 header("Location:$route");
   	 exit;
   }

	/**
 	 *	Allows overriding of the default routes.
	 *	@access protected
	 *	@param array $route_array
 	 */
   protected function set_routes($route_array)
   {
   	$this->route_array=$route_array;
   }

	/**
 	 *	Allows overriding of the default action.
	 *	@access protected
	 *	@param string $action
 	 */
   protected function set_action($action)
   {
   	$this->action=$action;
   }

	/**
 	 *	Renders the given view using PHPTAL and returns the html as a string.
	 *	@access protected
	 *	@param string $controller_name if not given defaults to current.
	 *	@param string $view_name
	 *	@param array $values Values to be passed to the template.
	 *	@return string
 	 */
   protected function view_to_string($controller_name=null, $view_name, $values=array())
   {
     $view_html='';
     if(!$controller_name) { $controller_name=substr( $this->class_name,0,strpos($this->class_name,"_")); }
     
   	 try {
			if(!$this->fetch_config("templating")=="php") {
   	  	$view=new PHPTAL(APP_DIR.'view/'.$controller_name."/".$view_name.".html");
				foreach($values as $k=>$v) {
	   	   $view->$k=$v;
	   	  }
	   	  $view_html=$view->execute();
			} else {
				$view= new WXTemplate;
				foreach($values as $k=>$v) {
	   	   $view->$k=$v;
	   	  }
				$view_html=$view->parse_no_buffer($controller_name."/".$view_name.".html/view");
			}   	  
   	 }
   	 catch(Exception $e) {
   	 	$this->process_exception($e);	
   	 }
   	 return $view_html;
   }

	/**
 	 *	Renders the given form using PHPTAL, adds any errors and returns the html as a string.
	 *	@access protected
	 *	@param string $form
	 *	@param array $values Values to be passed to the template.
	 *	@param $noerrors Defaults to null. If set errors will not be automatically prepended.
	 *	@return string
 	 */   
   protected function form_to_string($form, $values=array() )
   {
     try
   	 {
   	  $view=new PHPTAL(APP_DIR.'view/forms/'.$form.".html");
   	  foreach($values as $k=>$v)
   	  {
   	   $view->$k=$v;
   	  }
   	  $view_html.=$view->execute();
   	 }
   	 catch(Exception $e)
   	 {
   	 $this->process_exception($e);	
   	 }
   	 return $view_html;
   }

	/**
 	 *	Adds a message that will be displayed to the user on the next screen.
	 *	@access protected
	 *	@param string $message
 	 */
	public function add_user_message($message) {
		if(Session::add_message($message)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function add_helper($url, $helperfile) {
		$this->helpers[strtolower($url)]=$helperfile;
	}
	
	
	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions prior to running the action.
	 *	@access protected
 	 */
   protected function controller_global()
   {
   	
   }

	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions prior to running the action.
	 *	@access protected
 	 */
   protected function before_action($action)
   {

   }
	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions after execution.
	 *	@access protected
 	 */
	protected function after_action($action)
   {

   }

	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions after execution.
	 *	@access protected
 	 */
	protected function filter_routes()
   {
			if(array_key_exists( $this->route_array[0], $this->helpers) ) {
				return false;
			}
			if(count($this->route_array)>$this->accept_routes) {
				throw new Exception("No Action Defined");
			}
   }
	
	/**
	 * method overloading function
	 *
	 * @return void
	 **/	
	function __call($method, $args) {
		$helperresult=false;
		$arg1=$this->route_array[0];
		array_shift($this->route_array);
		$_GET['route']=$this->route_array;
		
		if(array_key_exists( $method, $this->helpers)) {
			$helper=$this->helpers[$method];
			$helper= new $helper;
			$method = new ReflectionMethod($helper, $arg1);
			if($method->isPublic()) {
				$helperresult=$helper->{$arg1}();
			}
		}
		if(!$helperresult) {
			if(method_exists($this, 'missing_action')) {
				$this->missing_action(); exit;
			}
			throw new Exception("No Action Defined for - ".$this->action);
		}
		exit;
	}
   
}

?>
