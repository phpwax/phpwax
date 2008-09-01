<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
 
/**
 *  Base exception class.
 *  Handling will depend upon the environment, in development mode errors are trapped and reported to the screen
 *  In production mode errors are handled quietly and optionally emailed or logged. 
 */
class WXException extends Exception
{
  
  static $redirect_on_error=false;
  static $double_redirect = false;
  static $email_on_error=false;
  static $email_subject_on_error="Application error on production server";
	public $div = "------------------------------------------------------------------------------------------------------\n";
  public $help = "No further information was available";

	public function __construct($message, $heading) {
    parent::__construct($message, $code);
    $this->error_heading = $heading;
    $this->error_message = $this->format_trace($this);
    $this->cli_error_message = $this->format_trace($this, true);
		if(defined('IN_CLI')) $this->cli_giveup();
		else $this->handle_error();
  }
  
	public function format_trace($e, $cli=false) {
	  if(IN_CLI =="true" || $cli) {
	    $view= new WXTemplate(array("e"=>$e, "help"=>$this->help));
  		$view->add_path(FRAMEWORK_DIR."/template/builtin/cli_trace");
  		return $view->parse();
	  }elseif(!self::$double_redirect) {
      self::$double_redirect=true;
      $view= new WXTemplate(array("e"=>$e, "help"=>$this->help));
  		$view->add_path(FRAMEWORK_DIR."/template/builtin/trace");
  		return $view->parse();
	  } else return $this->error_message;
	}
	
	
	public function handle_error() {
	  if($email = self::$email_on_error) {
  		mail($email, self::$email_subject_on_error, $this->cli_error_message);
	  }
	  if($location = self::$redirect_on_error) {
  	  error_log($this->cli_error_message);
      if(!self::$double_redirect) {
  	    self::$double_redirect = true;
        header("HTTP/1.1 500 Application Error",1, 500);  
        if(is_readable(PUBLIC_DIR.ltrim($location, "/")) ) {
          $content = file_get_contents(PUBLIC_DIR.ltrim($location, "/"));
          ob_end_clean();
          echo $content;
          exit;
        }
        $_GET["route"]=$location;
        $delegate = Inflections::slashcamelize(WaxUrl::get("controller"), true)."Controller";
  		  $delegate_controller = new $delegate;
  		  $delegate_controller->execute_request();
  		  exit;
		  }
  	}
		header("Status: 500 Application Error");
		echo $this->error_message;
		exit;
	}

	public function cli_giveup() {
		echo $this->error_message;
		exit;
	}
	
	
}

?>