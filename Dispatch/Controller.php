<?php
namespace Wax\Dispatch;
use Wax\Utilities\Session;
use Wax\Template\Template;

/**
 * @package PHP-Wax
 * Provides basic functionality which controllers inherit.
 */

class Controller
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
	
  public $view_paths = array();

	public function __construct($application=false) {
	  if($application instanceof WaxApplication) {
	    $this->application = $application;
	    $this->response = $this->application->response;
    } else {
	    $this->response = new Response;
    }
	  $this->init();    
  }
  
  public function init(){
    $this->class_name=get_class($this);
    $this->referrer=Session::get('referrer');
    $this->controller = Url::get("controller");
    $this->action = Url::get("action");
    $this->view_paths();
  }
  
  public function view_paths() {
    $this->view_paths[]=VIEW_DIR.$this->controller;
    $this->view_paths[]=VIEW_DIR."shared";
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

	
  
  /**
   *  Returns a view as a string.
	 *	@return string
 	 */
  public function render_view() {
		if(!$this->use_view) return false;
		if($this->use_view == "none") return false;
		if($this->use_view=="_default") $this->use_view = $this->action;

    $view = new Template($this);
    foreach($this->view_paths as $path) {
      $view->add_path($path."/".$this->use_view);
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
    $layout = new Template($this);
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

