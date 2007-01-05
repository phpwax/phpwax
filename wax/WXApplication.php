<?php
/**
 * 	@package php-wax
 */

/**
	* 	@package php-wax
  *   This is essentially a FrontController whose job in life
  *   is to parse the request and delegate the job to another controller that cares.
  *
  *   In making this decision it will consult the route configuration for guidance.
  *   It's also this lovely class's job to provide a limited amount of wiring to the rest of
  *   the application.
  */
  
class WXApplication
{

  public $config;

  /**
    *  Step 1. Expose the configuration to the application
    *  Step 2. Setup the environment. 
    *  @return array
    */

	function __construct() {
	  $this->config = new WXConfiguration;
	  $this->setup_environment();
	  $this->initialise_database($this->config->db);
	  $this->delegate_request();
  }


  /**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function setup_environment() {
		if(defined('ENV')) {
		  $this->config->switch_environment(ENV);
		} else {
		  $this->config->switch_environment('development');
		}
		Session::start();
  }
  
  private function initialise_database($db) {
    if(!$db['port']) $db['port']="3306";
    if(isset($db['socket']) && strlen($db['socket'])>2) {
			$dsn="{$db['dbtype']}:unix_socket={$db['socket']};dbname={$db['database']}"; 
		} else {
			$dsn="{$db['dbtype']}:host={$db['host']};port={$db['port']};dbname={$db['database']}";
		}
		$pdo = new PDO( $dsn, $db['username'] , $db['password'] );
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if(! WXActiveRecord::setDefaultPDO($pdo) ) {
    	throw new WXException("Cannot Initialise DB", "Database Configuration Error");
    }
  }
	
  
  /**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function delegate_request() {
		$route=new WXRoute($this->config->route);		
		$delegate = $route->pick_controller();
		$this->actions = $route->read_actions();
		$delegate = $this->run_controller($delegate);
		$this->create_page($delegate);
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
	private function run_controller($delegate) {
	  $this->action=$this->actions[0];
	  array_shift($this->actions);
	  $final_route=array_merge($_GET, $this->actions);
	  unset($final_route['route']);
	  if(strlen($this->action)<1) { $this->action="index"; }
	  $cnt=new $delegate();
	  $cnt->set_routes($final_route);
	  $cnt->set_action($this->action);
	  $cnt->controller_global();
	  if(!$this->is_public_method($cnt, $cnt->action)) {
	    if(method_exists($cnt, 'missing_action')) {
			  $cnt->missing_action(); return $cnt;
		  } else {
			  throw new WXRoutingException("No Public Action Defined for - ".$this->action." in controller {$controller}.", "Missing Action");
			  exit;
  		}
		}
	  $cnt->run_before_filters();
		$cnt->{$cnt->action}();
		$cnt->filter_routes();
		$cnt->run_after_filters();
		return $cnt;		
	}

  private function is_public_method($object, $method) {
    if(!method_exists($object, $method)) {
      return false;
    }
    $this_method = new ReflectionMethod($object, $method);
		if($this_method->isPublic()) {
		  return true;
		}
	  return false;
  }
	
	/**
	 *	Constructs the Output.
	 *  Uses the WXTemplate Class to set variables in view
	 *  @access private
   *  @return void
   */	
	private function create_page($cnt) {
	  if($cnt->use_view == "none") return true;
	  $this->controller = slashify(str_replace("Controller", "", $this->controller));
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
  	  if(!$use_view=$cnt->use_view) $use_view=$this->action; 
			if($cnt->use_plugin) {
				$tpl=new WXTemplate(false, $cnt->use_plugin, get_parent_class($cnt)."/".$use_view.".html");
			} else $tpl=new WXTemplate;
  		$tpl->urlid=$cnt->action;
      foreach(get_object_vars($cnt) as $var=>$val) {
        $tpl->{$var}=$val;
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
	}




}


?>
