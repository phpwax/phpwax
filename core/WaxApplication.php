<?php

/**
	*  @package PHP-Wax  
  *  This is essentially a FrontController whose job in life
  *  is to parse the request and delegate the job to another 
  *  controller that cares.
  *
  *  In making this decision it will consult the application configuration for guidance.
  *  It's also this lovely class's job to provide a limited amount of wiring to the rest of
  *  the application and setup some kind of Database Connection if required.
  *
  */
  
class WaxApplication {


  public $request = false;
  public $response = false;
  public $is_light = false;

  /**
    *  Step 1. Setup an environment. 
    *  Step 2. Find out if we're having a database and set it up.
    *  Step 3. Pass on the work to a delegate controller.
    *
    */

	public function __construct($delegate) {
    WaxEvent::run("wax.start");
    if(is_subclass_of($this->delegate_candidate(), "WaxControllerLight")) {
      $this->is_light = TRUE;
    }
    $this->setup_environment();	
  }


  /**
	 *	Instantiates a config object and constructs the route.  
	 *  @access private  
   *  @return void  
   **/
   
	private function setup_environment() {		
	  $addr = gethostbyname($_SERVER["HOSTNAME"]);
	  if(!$addr) $addr = gethostbyname($_SERVER["SERVER_NAME"]);
	  $regexp = '/^((1?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(1?\d{1,2}|2[0-4]\d|25[0-5])$/'; 
	  if(!preg_match($regexp, $addr)) $addr = false;
		if(defined('ENV')) {
		  Config::set_environment(ENV);
		} elseif($addr && (substr($addr,0,3)=="10." || substr($addr,0,4)=="127."||substr($addr,0,4)=="192.")) {
		  Config::set_environment('development');
		  define("ENV", "development");
		} elseif($addr) {
		  Config::set_environment('production');
		  define("ENV", "production");
		} else Config::set_environment('development');
		//  Looks for an environment specific file inside app/config or light.php for a light controller setup
		if($this->is_light) {
		  if(is_readable(CONFIG_DIR."light.php")) require_once(CONFIG_DIR."light.php");
		} else {
		  if(is_readable(CONFIG_DIR."global.php")) require_once(CONFIG_DIR."global.php");
		  if(is_readable(CONFIG_DIR.ENV.".php")) require_once(CONFIG_DIR.ENV.".php");
    }
  }
  
  /**
	 *	Instantiates a database connection. It requires PDO which is available in PHP 5.1
	 *  It then passes this information to the ActiveRecord object.
	 *
	 *  A few defaults are allowed in case you are too lazy to specify.
	 *  Dbtype defaults to *mysql*
	 *  Host defaults to *localhost*
	 *  Port defaults to *3306*
	 *
	 *  @access private  
   *  @return void
   */
  
  public function initialise_database() {
    if($db = Config::get('db')) {
      if($db['dbtype']=="none") return false;
      WaxModel::load_adapter($db);
    }
  }
	

  /**
	 *	The main application method, triggers all application events.
	 *  Delegates response handling to WaxResponse.
   *
   **/
   
  public function execute() {
    Session::start();
    WaxEvent::run("wax.request");
	  $this->request = WaxUrl::$params;
	  WaxEvent::run("wax.post_request", $this->request);
	  $this->response = new WaxResponse;
	  
	  $delegate = $this->delegate_candidate();
    $controller = new $delegate($this);
	  WaxEvent::run("wax.controller", $controller);
	  WaxEvent::run("wax.pre_render", $controller);
	  if($controller->render !==false) $this->execute_controller($controller);    
		WaxEvent::run("wax.post_render", $this->response);
		$this->response->execute();
  }
  
  /**
   * Returns a delegate candidate that can handle our request
   *
   * @return string $controller
   **/
  public function delegate_candidate() {
    return Inflections::slashcamelize(WaxUrl::get("controller"), true)."Controller";
  }
  
  
  /**
	 *	Takes the controller and triggers the relevant action.
	 *  @param WaxController $controller
   *
   **/
  
  public function execute_controller(&$controller) {	      
    $controller->controller = WaxUrl::get("controller");
	  $controller->action = WaxUrl::get("action");
	  $controller->route_array = explode("/", trim(WaxUrl::$original_route,"/"));
	  $controller->use_format = WaxUrl::get("format");
	  
    WaxEvent::run("wax.controller_global", $controller);
	  $controller->controller_global();	  
    WaxEvent::run("wax.before_filter", $controller);
	  $controller->run_filters("before");
	  if(!$this->is_public_method($controller, $controller->action)) {
	    if($this->is_public_method($controller, Inflections::underscore($controller->action))) {
	      $underscore_action = Inflections::underscore($controller->action);
	      WaxEvent::run("wax.action", $controller);
	      $controller->{$underscore_action}();
	    } elseif(method_exists($controller, 'method_missing') ) {
			  $controller->method_missing();
		  } else {  	    
		    $class=get_class($controller);
		    WaxEvent::run("wax.404", $controller);
			  throw new WXRoutingException("No Public Action Defined for - ".$controller->action." in controller {$class}.", "Missing Action");
  		}
		} else {
		  WaxEvent::run("wax.action", $controller);
		  $controller->{$controller->action}();
		}
		
		WaxEvent::run("wax.after_filter", $controller);
		$controller->run_filters("after");		
		$controller->content_for_layout = $controller->render_view();
		WaxEvent::run("wax.layout", $controller);
	  
		if($content = $controller->render_layout()) $this->response->write($content);
		elseif($controller->content_for_layout) $this->response->write($controller->content_for_layout);
		else $this->response->write(""); 
  }
  
  //### Application Helper Methods
  
  /**
   *  Surely it's self-documenting?.
	 *	@return bool
 	 **/
 	 
	public static function is_public_method($object, $method) {
    if(!method_exists($object, $method)) return false;
    $this_method = new ReflectionMethod($object, $method);
		if($this_method->isPublic()) return true;
	  return false;
  }


}

