<?php
/**
 * 	@package PHP-Wax
 */

/**
	* 	@package PHP-Wax
  *   This is essentially a FrontController whose job in life
  *   is to parse the request and delegate the job to another controller that cares.
  *
  *   In making this decision it will consult the application configuration for guidance.
  *   It's also this lovely class's job to provide a limited amount of wiring to the rest of
  *   the application and setup some kind of Database Connection if required.
  *
  *   
  *
  */
  
class WXApplication {


  /**
    *  Step 1. Setup an environment. 
    *  Step 2. Find out if we're having a database and set it up.
    *  Step 3. Pass on the work to a delegate controller.
    *
    */

	function __construct($delegate) {
	  $this->setup_environment();	
	  $this->initialise_database();
	  if($delegate) $this->delegate_request();
  }


  /**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function setup_environment() {
	  $addr = gethostbyname($_SERVER["HOSTNAME"]);
	  if(!$addr) $addr = gethostbyname($_SERVER["HTTP_HOST"]);
	  $regexp = '/^((1?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(1?\d{1,2}|2[0-4]\d|25[0-5])$/'; 
	  error_log($addr);
	  if(!preg_match($regexp, $addr)) $addr = false;
		if(defined('ENV')) {
		  WXConfiguration::set_environment(ENV);
		} elseif($addr && (substr($addr,0,3)=="10." || substr($addr,0,4)=="127."||substr($addr,0,4)=="192.")) {
		  WXConfiguration::set_environment('development');
		  define("ENV", "development");
		} elseif($addr) {
		  WXConfiguration::set_environment('production');
		  define("ENV", "production");
		} else WXConfiguration::set_environment('development');
			  
		/*  Looks for an environment specific file inside app/config */
		if(is_readable(CONFIG_DIR.ENV.".php")) require_once(CONFIG_DIR.ENV.".php");
		WaxLog::log("info", "Detected environment $addr and loaded ".ENV);
	  
  }
  
  /**
	 *	Instantiates a database connection. It requires PDO which is available in PHP 5.1
	 *  It then passes this information to the ActiveRecord object.
	 *
	 *  A few defaults are allowed in case you are too lazy to specify.
	 *  Dbtype defaults to mysql
	 *  Host defaults to localhost
	 *  Port defaults to 3306
	 *  
	 *
	 *  @access private
   *  @return void
   */
  
  private function initialise_database() {
    if($db = WXConfiguration::get('db')) {
      if($db['dbtype']=="none") return false;
      if(!$db['host']) $db['host']="localhost";
      if(!$db['port']) $db['port']="3306";
      
    /****** Deprecated support for WXActiveRecord only around for one more version *****/
      WXActiveRecord::$pdo_settings = $db; 
    /**********************************************************/
      
      WaxModel::load_adapter($db);
    }
  }
	
  
  /**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function delegate_request() {
	  Session::start();
	  $delegate = Inflections::slashcamelize(WaxUrl::get("controller"), true)."Controller";
		$delegate_controller = new $delegate;
		$delegate_controller->execute_request();
  }


}


?>
