<?php
/**
 *  exception class for WXActiveRecord
 */
class WXException extends Exception
{
  
  static $redirect_on_error=false;
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
        table { border:1px solid #ccc; border-collapse: collapse; width:100%; background:white; }
        tbody td, tbody th { vertical-align:top; padding:2px 3px; }
        thead th { padding:1px 6px 1px 3px; background:#fefefe; text-align:left; font-weight:normal; font-size:11px; border:1px solid #ddd; }
        tbody th { width:12em; text-align:right; color:#666; padding-right:.5em; }
        ul { margin-left: 2em; margin-top: 1em; }
        #summary { background: #e0ebff; }
        #summary h2 { font-weight: normal; color: #666; }
        #instructions { background:#f6f6f6; }
        #summary table { border:none; background:transparent; }
      </style>';
  	$trace.="<title>{$this->error_heading}</title>";
    $trace.='</head><body><div id="summary">';
    $trace.="<h1>{$e->error_heading}</h1>\n";
    $trace.="<h2>{$e->getMessage()}</h2>\n</div>";
    $trace.='<div id="instructions">';
    $trace.="<pre><p>{$e->getTraceAsString()}</p>\n";
    $trace.="<code>{$e->getFile()}\nLine: {$e->getLine()}</code></pre>\n";
    $trace.="</div>\n";
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
  	  header("Status: 404 Not Found");
  	  header("Location: /{$location}");
  	  exit;
  	}
		header("Status: 500 Application Error");
		echo $this->simple_error_message;
		error_log($this->cli_error_message);
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