<?php

class WaxException extends Exception {
  
  static $redirect_on_error=false;
  static $double_redirect = false;
  static $email_on_error=false;
  static $email_subject_on_error="Application error on production server";
	public $div = "------------------------------------------------------------------------------------------------------\n";
  public $help;
  static $replacements = array('error_message'=> '<!-- MESSAGE -->', 'error_heading'=>'<!-- HEADING -->', 'error_site'=>'<!-- SITE -->', 'error_site_name'=> '<!-- SITENAME -->');

	public function __construct($message, $heading="Application Error", $context=false, $data = array()) {
    parent::__construct($message, $code);
    foreach($data as $k => $v) $this->$k = $v;
    $this->error_heading = $heading;
		if($context) $this->help = $context;
    $this->error_message = $this->format_trace($this);
		$this->error_site = str_ireplace("www.", '', $_SERVER['HTTP_HOST']);
		$this->error_site = substr($this->error_site, 0, strpos($this->error_site, '.'));
		$this->error_site_name = ucwords(Inflections::humanize($this->error_site));
		if(defined('IN_CLI')) $this->cli_giveup();
		else $this->handle_error();
  }
  
	public function format_trace($e, $cli=false) {
      $vars = isset($this->vars) ? $this->vars : null;
      $trace = isset($this->trace) ? $this->trace : null;
	  if(defined('IN_CLI') && (IN_CLI =="true" || $cli)) {
	    $view = new WaxTemplate(array("e"=>$e, "help"=>$this->help, "file"=>$this->file, "line"=>$this->line, "vars"=>$vars, "trace"=>$trace));
			$view->use_cache=false;
  		$view->add_path(FRAMEWORK_DIR."/template/builtin/cli_trace");
  		return $view->parse();
	  }else {
	    $view = new WaxTemplate(array("e"=>$e, "help"=>$this->help, "file"=>$this->file, "line"=>$this->line, "vars"=>$vars, "trace"=>$trace));
	    $view->use_cache=false;
  		$view->add_path(FRAMEWORK_DIR."/template/builtin/trace");
  		return $view->parse();
	  }
	}
	
	
	public function handle_error() {
	  if($email = self::$email_on_error) {
  		mail($email, self::$email_subject_on_error, $this->cli_error_message);
	  }
	  if($location = self::$redirect_on_error) {
  	  WaxLog::log("error", $this->format_trace($this, true));
      if(!self::$double_redirect) {
  	    self::$double_redirect = true;
        header("HTTP/1.1 500 Application Error",1, 500);  
        if(is_readable(PUBLIC_DIR.ltrim($location, "/")) ) {
          $content = file_get_contents(PUBLIC_DIR.ltrim($location, "/"));
          ob_end_clean();
					foreach(self::$replacements as $value=>$replace) $content = str_ireplace($replace, $this->$value, $content);
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
		header("HTTP/1.1 500 Application Error", 1, 500);
		echo $this->error_message;
		exit;
	}

	public function cli_giveup() {
		echo $this->error_message;
		exit;
	}
  
}


