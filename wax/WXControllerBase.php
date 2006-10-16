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
  protected $action;
  public $use_layout='application';
  public $use_view=null;
  private $class_name='';
  protected $referrer;
	protected $user_messages=array();
	protected $user_errors=array();
	protected $show_errors=true;
	protected $show_messages=true;
	protected $use_plugin=false;
	public $body_js_files=array();
	
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
   /*
    * This array contains the info that is passed on to the layout.
    * public functions are provided to add variables to this, do not
    * set directly.
    */
   public $layout=array(
        'styles'=>    array(
                              
                           ),
        'scripts'=>   array(
                            '/javascript/lib/form.behaviours.js',							
                           ),
         'meta'=>     array()
   );
  
   function __construct()
   {      
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

	/**
 	 *	Adds custom meta data which are passed onto the template.
	 *	@access protected
	 *	@param string $name
	 *	@param string $content
 	 */   
  protected function add_meta_content($name, $content) {
   	$this->layout['meta'][$name]=$content; return true;   
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
		if(!$helperresult) {
			if(method_exists($this, 'missing_action')) {
				$this->missing_action(); exit;
			}
			throw new WXException("No Action Defined for - ".$this->action, "Missing Action");
		}
		exit;
	}
   
}

?>
