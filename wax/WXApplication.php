<?php
/**
 * 	@package php-wax
 */

/**
	* 	@package php-wax
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

	function __construct($delegate=true) {    
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
		if(defined('ENV')) {
		  WXConfiguration::set_environment(ENV);
		} else {
		  WXConfiguration::set_environment('development');
		}
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
      if(!$db['dbtype']) $db['dbtype']="mysql";
      if(!$db['host']) $db['host']="localhost";
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
  }
	
  
  /**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function delegate_request() {
	  Session::start();
		$route=new WXRoute( );
		$delegate = $route->pick_controller();
		$delegate_controller = new $delegate;
		$delegate_controller->execute_request();
  }


}


?>
