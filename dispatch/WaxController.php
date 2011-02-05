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
  }
  
  

	/**
 	 *	Sends a header redirect, moving the app to a new url.
	 *	@access protected
	 *	@param string $route
 	 */   
  protected function redirect_to($options, $protocol="http://") {
    switch(true) {
      case is_array($options):
        $url = $protocol.$_SERVER['HTTP_HOST'].UrlHelper::url_for($options);
        $this->response->redirect($url);
        break;
      case preg_match("/^\w+:\/\/.*/", $options):
        $this->response->redirect($options);
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
        $this->response->redirect($url);
        break;
    }
    $this->response->execute();
    exit;
  }

	
  
  /**
   *  Returns a view as a string.
	 *	@return string
 	 */
  public function render_view() {
		if(!$this->use_view) return false;
		if($this->use_view == "none") return false;
		if($this->use_view=="_default") $this->use_view = $this->action;

    $view = new WaxTemplate($this);
    foreach(Wax::view_paths("user") as $path) {
      $view->add_path($path.rtrim($this->controller,"/")."/".$this->use_view);
      $view->add_path($path."shared/".$this->use_view);
      $view->add_path($path.$this->use_view);
    }

    foreach((array)Wax::view_paths("plugin") as $path) {
      $view->add_path($path.get_class($this)."/".$this->use_view);
      $view->add_path($path.get_parent_class($this)."/".$this->use_view);
      $view->add_path($path."shared/".$this->use_view);
    }
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

