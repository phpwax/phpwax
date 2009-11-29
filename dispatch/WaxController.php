<?php


/**
 * @package PHP-Wax
 * Provides basic functionality which controllers inherit.
 */

class WaxController
{

  protected $route_array=null;
  public $controller;
  public $action;
  public $use_layout='application';
  public $use_view="_default";
  public $use_format="html";
  protected $class_name='';
  public $referrer;
	public $use_plugin=false;
	public $shared_plugin=false;
	public $plugin_share = 'shared';
	public $filters = array(); 
	public $plugins = array();
  //with this you can override the cache settings and turn it of on the application level ignoring the config
  public $use_cache = true; 

	public function __construct($run_init=true) {
	  if($run_init) $this->init();    
  }
  
  public function init(){
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
  public function redirect_to($options, $protocol="http://") {
    switch(true) {
      case is_array($options):
        $url = $protocol.$_SERVER['HTTP_HOST'].UrlHelper::url_for($options);
        header("Location:$url"); exit;
        break;
      case preg_match("/^\w+:\/\/.*/", $options): 
        header("Location:$options"); exit;
        break;
      case $options=="back":
        if(!$_SERVER['HTTP_REFERER']) return false;
        header("Location:{$_SERVER['HTTP_REFERER']}"); exit;
        break;
      case is_string($options):
        if(substr($options,0,1)!="/"){
          if(substr($_SERVER['REQUEST_URI'],-1) != "/") $options = "/" . $options;
          $options = $_SERVER['REQUEST_URI'] . $options;
        }
        $url = $protocol.$_SERVER['HTTP_HOST'].$options;
        header("Location:$url"); exit;
        break;
    }
  }

	public function run_filters($when) {
		if(!is_array($this->filters[$when])) return false;
    foreach($this->filters[$when] as $action=>$filter) {
      if(is_array($filter) && $action=="all" && is_array($filter[1])) {
        if(!in_array($this->action,$filter[1])) $this->$filter[0]();
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
	  //if($param=="id") return $this->route_array[0];
	  return Request::get($param);
	  if(isset($this->route_array[$param])) return addslashes($this->route_array[$param]);
	  if(isset($_GET[$param])) return addslashes($_GET[$param]);
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
   *  Adds a plugin to the array.
	 *	@return void
 	 */
  public function add_plugin($plugin) {
    $this->plugins[]=$plugin;
  }
  

	
  
  /**
   *  Returns a view as a string.
	 *	@return string
 	 */
  protected function render_view() {
    if($this->use_plugin) {
      WaxLog::log("info", "[DEPRECATION] use_plugin in controllers is deprecated, use the add_plugin method intead.");
      $this->add_plugin($this->use_plugin);
    }
    if($this->shared_plugin) {
      WaxLog::log("info", "[DEPRECATION] shared_plugin in controllers is deprecated, use the add_plugin method intead.");
      $this->add_plugin($this->shared_plugin);
    }
		if(!$this->use_view) return false;
		if($this->use_view == "none") return false;
		if($this->use_view=="_default") $this->use_view = $this->action;

    $view = new WaxTemplate($this);
    foreach(Autoloader::view_paths("user") as $path) {
      $view->add_path($path.rtrim($this->controller,"/")."/".$this->use_view);
      $view->add_path($path."shared/".$this->use_view);
      $view->add_path($path.$this->use_view);
    }
    
    foreach(Autoloader::view_paths("plugin") as $path) {
      $view->add_path($path.get_parent_class($this)."/".$this->use_view);
      $view->add_path($path."shared/".$this->use_view);
    }
    ob_end_clean();
    if($this->use_format) $content = $view->parse($this->use_format, 'views');
		else $content = $view->parse('html', 'views');
		return $content;
  }
  
  /**
   *  Returns a layout as a string.
	 *	@return string
 	 */
  protected function render_layout() {
		if(!$this->use_layout) return false;
    $layout = new WaxTemplate($this);
    $layout->add_path(VIEW_DIR."layouts/".$this->use_layout);

    $layout->add_path(PLUGIN_DIR.$this->use_plugin."/view/layouts/".$this->use_layout);
    $layout->add_path(PLUGIN_DIR.$this->share_plugin."/view/layouts/".$this->use_layout);
    ob_end_clean();
	  return $layout->parse($this->use_format);      
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
	  }else{
	    $partial = $path;
	    $path = "_".$path;
	  }
		if($this->is_public_method($this, $partial."_partial")) $this->{$partial."_partial"}();
	  $partial= $this->build_partial($path);		
		return $partial;
	}
	
	public function build_partial($path, $format = false) {
	  $partial = new WaxTemplate($this);
    $partial->add_path(VIEW_DIR.$path);
    $partial->add_path(VIEW_DIR.$this->controller."/".$path);
    
    $partial->add_path(PLUGIN_DIR.$this->use_plugin."/view/".get_parent_class($this)."/".$path);
    $partial->add_path(PLUGIN_DIR.$this->use_plugin."/view/".$this->plugin_share."/".$path);
    $partial->add_path(PLUGIN_DIR.$this->share_plugin."/view/".get_parent_class($this)."/".$path);
    $partial->add_path(PLUGIN_DIR.$this->share_plugin."/view/".$this->plugin_share."/".$path);
    if($format) $this->use_format = $format;
    return $partial->parse($this->use_format, "partial");
	}
	
	public function execute_partial($path, $format = false) {
	  if(strpos($path, "/")) {
	    $partial = substr($path, strrpos($path, "/")+1);
	    $path = substr($path, 0, strrpos($path, "/")+1);
	    $path = $path.$partial;
	  } else $partial = $path;
	  if($this->is_public_method($this, $partial)) $this->{$partial}();
	  $partial= $this->build_partial($partial, $format);		
		return $partial;
	}
	
	
	/**
   *  Returns a single path as a string.
	 *	@return string
 	 */
	public function view_to_string($view_path, $values=array(), $suffix="html") {
		$view= new WaxTemplate($values);
		$view->add_path(VIEW_DIR.$view_path);
		if($this->use_format) return $view->parse($this->use_format);
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
	  $this->controller = WaxUrl::get("controller");    
	  $this->action = WaxUrl::get("action");
	  $this->route_array = explode("/", $_GET["route"]);
	  $this->use_format = WaxUrl::get("format");
	  WaxLog::log("info", "Loading controller {$this->controller} with action {$this->action} from route '{$_GET['route']}'");
	  $this->controller_global();
	  $this->run_filters("before");
	  if(!$this->is_public_method($this, $this->action)) {
	    if($this->is_public_method($this, Inflections::underscore($this->action))) {
	      $underscore_action = Inflections::underscore($this->action);
	      $this->{$underscore_action}();
	    } elseif(method_exists($this, 'method_missing') ) {
			  $this->method_missing();
		  } else {  	    
		    $class=get_class($this);
			  throw new WXRoutingException("No Public Action Defined for - ".$this->action." in controller {$class}.", "Missing Action");
  		}
		} else {
		  $this->{$this->action}();
		}
		$this->run_filters("after");		
		$this->content_for_layout = $this->render_view();
	  //if(!headers_sent()) header("Content-Type: $type; charset=utf-8");
		if($content = $this->render_layout()) echo $content;
		elseif($this->content_for_layout) echo $this->content_for_layout;
		else echo "";
	}
	

  public function is_viewable($path, $format="html"){
		$file_path = VIEW_DIR . $path . ".". $format;
		if(is_readable($file_path)) return true;
		else return false;
	}
	
	

}

