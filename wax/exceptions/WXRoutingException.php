<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley & charles marshall
 **/
class WXRoutingException extends WXException
{
  static $redirect_on_error=false;
  static $double_redirect = false;
  
	function __construct( $message, $code="Page cannot be found", $status = "404" ) {  	
		
  	if($location = self::$redirect_on_error) {
  	  $this->simple_routing_error_log();
  	  if(!self::$double_redirect) {
  	    self::$double_redirect = true;
        header("HTTP/1.1 404 Not Found",1, 404);  
        $_GET["route"]=$location;
				WaxUrl::$params = false;
				WaxUrl::perform_mappings();
        $delegate = Inflections::slashcamelize(WaxUrl::get("controller"), true)."Controller";
  		  $delegate_controller = new $delegate;
  		  $delegate_controller->execute_request();
  		  exit;
		  } else {
		    $code ="Application Error"; 
		    $message = "A Page not found error was triggered and you have not set up a page to handle it";
		  }
  	}
  	parent::__construct($message, $code);
  }
  
  function simple_routing_error_log() {
    WaxLog::log("error", "[Routing] Couldn't load a requested page: {$_GET['route']}");
  }
}


?>