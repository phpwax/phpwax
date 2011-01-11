<?php


/**
 * @package PHP-Wax
 * Provides raw metal controller functionality for fast serving of fragments 
 * Satisfies the same interface of a standard WaxController with the following differences:
 *      Several methods are overwritten to be empty:
 *        all filter methods
 *        all plugin methods
 *      No responsibility from the framework to load anything other than dispatch and core support.
 *      If you need to use the database set it up on a per method basis calling $this->use_db().
 *      Anything else non core - load it in manually, this includes forms, helpers etc.
 */

class WaxControllerLight extends WaxController {
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
  
  public function use_db() {
    Wax::register_directory(FRAMEWORK_DIR."/db");
    if($db = Config::get('db')) {
      if($db['dbtype']=="none") return false;
      WaxModel::load_adapter($db);
    }
  }


	
	/**
   *  Not implemented in BareController.
	 *	@return void
 	 */
 	public function run_filters($when) {}

  public function before_filter($action, $action_to_run, $except=null) {}
  
  public function after_filter($action, $action_to_run, $except=null) { }

  
  /**
   *  Not implemented in BareController.
	 *	@return void
 	 */
  public function add_plugin($plugin) {}

	
  
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
    if($this->use_format) $content = $view->parse($this->use_format, 'views');
		else $content = $view->parse('html', 'views');
		return $content;
  }
  
  /**
   *  Returns a layout as a string.
   *  Note: no plugin support in this version of the controller class
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
    * Empty for a bare controller
    */
   public function is_viewable($path, $format="html"){}

 
}