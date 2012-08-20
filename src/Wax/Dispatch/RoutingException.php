<?php
namespace Wax\Dispatch;
use Wax\Template\Helper\Inflections;
use Wax\Utilities\Log;
use Wax\Core\Exception;

/**
 *
 * @package PHP-Wax
 * @author Ross Riley & charles marshall
 **/
class RoutingException extends Exception {

  static $redirect_on_error=false;
  static $double_redirect = false;
  
	function __construct( $message, $code="Page cannot be found", $status = "404" ) {  	
		
  	if($location = self::$redirect_on_error) {
			$this->error_heading = $code;
	    $this->error_message = $this->format_trace($this);
			$this->error_site = str_ireplace("www.", '', $_SERVER['HTTP_HOST']);
			$this->error_site = substr($this->error_site, 0, strpos($this->error_site, '.'));
			$this->error_site_name = ucwords(Inflections::humanize($this->error_site));
  	  $this->simple_routing_error_log();
  	  if(!self::$double_redirect) {
  	    self::$double_redirect = true;
        header("HTTP/1.1 404 Not Found",1, 404);
        if(is_readable(PUBLIC_DIR.ltrim($location, "/")) ) {
          $content = file_get_contents(PUBLIC_DIR.ltrim($location, "/"));
					foreach(self::$replacements as $value=>$replace) $content = str_ireplace($replace, $this->$value, $content);
          ob_end_clean();
          echo $content;
          exit;
        }  
        $_GET["route"]=$location;
				Url::$params = false;
				Url::perform_mappings();
        $delegate = Inflections::slashcamelize(Url::get("controller"), true)."Controller";
  		  $delegate_controller = new $delegate;
  		  $delegate_controller->execute_request();
  		  exit;
		  } else {
		    Log::log("error", "[Routing] Double redirect error");
		    $code ="Application Error"; 
		    $message = "A Page not found error was triggered and you have not set up a page to handle it";
		  }
  	}
  	parent::__construct($message, $code);
  }
  
  function simple_routing_error_log() {
    Log::log("error", "[Routing] Couldn't load a requested page: {$_GET['route']}");
  }
}


?>