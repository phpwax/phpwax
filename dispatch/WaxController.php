<?php


/**
 * @package PHP-Wax
 * Provides basic functionality which controllers inherit.
 */

class WaxController
{
  protected $class_name='';
  
  // A reference to the master application object
  public $application = false;
  public $response = false;
  
  public $route_array=null;
  public $controller;
  public $action;
  public $use_layout='application';
  public $use_view="_default";
  public $use_format="html";
  public $referrer;
	public $use_plugin=false;
	public $shared_plugin=false;
	public $plugin_share = 'shared';
	public $filters = array(); 
	public $plugins = array();
	
	// Flag which can be set to false to render nothing
	public $render = true;
	
  //with this you can override the cache settings and turn it of on the application level ignoring the config
  public $use_cache = true;

	public function __construct($application=false) {
	  if($application instanceof WaxApplication) {
	    $this->application = $application;
	    $this->response = $this->application->response;
    } else {
	    $this->response = new WaxResponse;
    }
	  $this->init();    
  }
  
  public function init(){
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
  public function redirect_to($options, $protocol="http://", $status=302) {
    switch(true) {
      case is_array($options):
        $url = $protocol.$_SERVER['HTTP_HOST'].UrlHelper::url_for($options);
        $this->response->redirect($url, $status);
        break;
      case preg_match("/^\w+:\/\/.*/", $options):
        $this->response->redirect($options,$status);
        break;
      case $options=="back":
        if(!$_SERVER['HTTP_REFERER']) return false;
        $this->response->redirect($_SERVER['HTTP_REFERER']);
        break;
      case is_string($options):
        if(substr($options,0,1)!="/"){
          if(substr($_SERVER['REQUEST_URI'],-1) != "/") $options = "/" . $options;
          $options = $_SERVER['REQUEST_URI'] . $options;
        }
        $url = $protocol.$_SERVER['HTTP_HOST'].$options;
        $this->response->redirect($url,$status);
        break;
    }
    $this->response->execute();
    exit;
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
  public function render_view() {
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
    WaxEvent::run("wax.after_local_view_paths", $view);

    foreach((array)Autoloader::view_paths("plugin") as $path) {
      $view->add_path($path.get_class($this)."/".$this->use_view);
      $view->add_path($path.get_parent_class($this)."/".$this->use_view);
      $view->add_path($path."shared/".$this->use_view);
    }
    WaxEvent::run("wax.after_plugin_view_paths", $view);
    
    if($this->use_format) $content = $view->parse($this->use_format, 'views');
		else $content = $view->parse('html', 'views');
		return $content;
  }
  
  /**
   *  Returns a layout as a string.
	 *	@return string
 	 */
  public function render_layout() {
		if(!$this->use_layout) return "";
    $layout = new WaxTemplate($this);
    $layout->add_path(VIEW_DIR."layouts/".$this->use_layout);
    foreach((array)Autoloader::view_paths("plugin") as $path) $layout->add_path($path."layouts/".$this->use_layout);
    ob_end_clean();
	  return $layout->parse($this->use_format);      
  }
  
  
  public function is_public_method($object, $method) {
    return WaxApplication::is_public_method($object, $method);
  }
  
	/**
 	 *	In the abstract class this remains empty. It is overridden by the controller,
	 *	any commands will be run by all actions prior to running the action.
	 *	@access protected
 	 */
   public function controller_global() {}
   
   /**
    * Added back in as this is heavily used by the cms
    */
   public function is_viewable($path, $format="html"){
     $file_path = VIEW_DIR . $path . ".". $format;
     if(is_readable($file_path)) return true;
     else return false;
   }


}

