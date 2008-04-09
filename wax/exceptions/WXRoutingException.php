<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WXRoutingException extends WXException
{
  static $redirect_on_error=false;
  
	function __construct( $message, $code="Page cannot be found", $status = "404" ) {  	
  	if($location = self::$redirect_on_error) {
  	  $this->simple_routing_error_log();
      header("HTTP/1.1 404 Not Found",1, 404);  
      $_GET["route"]=$location;
      $delegate = Inflections::slashcamelize(WaxUrl::get("controller"), true)."Controller";
  		$delegate_controller = new $delegate;
  		$delegate_controller->execute_request();
  	}
  	parent::__construct($message, $code);
  }
  
  function simple_routing_error_log() {
    error_log($this->getMessage());
  }
}


?>