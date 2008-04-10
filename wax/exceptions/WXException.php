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

	public function __construct($message, $heading, $code = "500") {
      parent::__construct($message, $code);
      $this->error_heading = $heading;
      $this->error_message = $this->format_trace($this);
			$this->simple_error_message = $this->simple_error();
      $this->error_code = $code;
			$this->cli_error_message = $this->cli_format_trace($this);
			if(ENV =="production") {
				$this->prod_giveup();
			} elseif(defined('CLI_ENV')) {
				$this->cli_giveup();
			} else {
				$this->dev_giveup();
			}
  }
	public function format_trace($e) {
		$post = var_export($_POST, 1);
		$get = var_export($_GET, 1);
		$cookie = var_export($_COOKIE, 1);
		$server = var_export($_SERVER, 1);
	  $trace.='
	  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
    <html lang="en"><head>
      <meta http-equiv="content-type" content="text/html; charset=utf-8">
      <style type="text/css">
        html * { padding:0; margin:0; }
        body * { padding:10px 20px; }
        body * * { padding:0; }
        body { font:small sans-serif; }
        body>div { border-bottom:1px solid #ddd; }
        h1 { font-weight:normal; }
        h2 { margin-bottom:.8em; }
        h2 span { font-size:80%; color:#666; font-weight:normal; }
        h3 { margin:1em 0 .5em 0; }
        h4 { margin:0 0 .5em 0; font-weight: normal; }
        ul { margin-left: 2em; margin-top: 1em; }
        #summary { background: #000000; color:white; }
        #summary h2, #environment h2 { font-weight: normal; color: #e1e1e1; }
        #instructions { background:#f6f6f6; }
				#environment { background:#f6f6f6; }
				#environment h2 {color:#111111;}
				#info {display:none;}
      </style>';
  	$trace.="<title>{$this->error_heading}</title>";
    $trace.='</head><body><div id="summary">';
    $trace.="<h1>{$e->error_heading}</h1>\n";
    $trace.="<h2>{$e->getMessage()}</h2>\n</div>";
    $trace.='<div id="instructions">';
    $trace.="<pre><p>{$e->getTraceAsString()}</p>\n";
    $trace.="<code>{$e->getFile()}\nLine: {$e->getLine()}</code></pre>\n";
    $trace.="</div>\n";
    $trace.='<div id="environment">';
    $trace.="<h2>HTTP Request, cookie and server information</h2>\n";
    $trace.="<p><a href='#' onclick='document.getElementById(\"info\").style.display=\"block\"'>View</a></p>\n";
    $trace.="<pre id='info'><p>Post $post</p>\n";
    $trace.="<p>Get $get</p>\n";
    $trace.="<p>Cookie $cookie</p>\n";
    $trace.="<p>Server $server</p>\n";
    $trace.="</pre></div>\n";


		return $trace;
	}
	public function simple_error() {
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
		$trace.="<title>Application Error</title>";
    $trace.="<h1>Application Error</h1>\n";
		return $trace;
	}

	public function dev_giveup() {
		header("Status: 500 Application Error");
		echo $this->error_message;
		exit;
	}
	public function prod_giveup() {
	  if($email = self::$email_on_error) {
  		error_log($this->cli_error_message);
  		mail($email, self::$email_subject_on_error, $this->cli_error_message);
	  }
	  if($location = self::$redirect_on_error) {
  	  error_log($this->getMessage());
  	  static $double_redirect = false;
      if(!self::$double_redirect) {
  	    self::$double_redirect = true;
        header("HTTP/1.1 500 Application Error",1, 500);  
        $_GET["route"]=$location;
        $delegate = Inflections::slashcamelize(WaxUrl::get("controller"), true)."Controller";
  		  $delegate_controller = new $delegate;
  		  $delegate_controller->execute_request();
  		  exit;
		  }
  	}
		header("Status: 500 Application Error");
		echo $this->simple_error_message;
		$all_lines = explode("\n", $this->cli_error_message);
    foreach($all_lines as $line) error_log($line);
		exit;
	}
	public function cli_giveup() {
		echo $this->cli_error_message;
		exit;
	}
	public function cli_format_trace($e) {
		$trace.= $this->div;
    $trace.="{$e->error_heading}\n";
		$trace.= $this->div;
    $trace.="{$e->getMessage()}\n";
		$trace.= $this->div;
    $trace.="{$e->getTraceAsString()}\n";
		$trace.= $this->div;
    $trace.="In {$e->getFile()} and on Line: {$e->getLine()}\n";
		$trace.= $this->div;
		return $trace;
	}
	
}

?>