<?php
/**
 *  exception class for WXActiveRecord
 */
class WXException extends Exception
{
	public function __construct($message, $heading, $code = "500") {
      parent::__construct($message, $code);
			$config = new ConfigBase;
      $this->error_heading = $heading;
      $this->error_message = $this->format_trace($this);
			$this->simple_error_message = $this->simple_error();
      $this->error_code = $code;
			if($config->return_config("environment")=="production") {
				$this->prod_giveup();
			} else {
				$this->dev_giveup();
			}
  }
	public function format_trace($e) {
		$trace.="<title>{$this->error_heading}</title>";
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
    $trace.="<h1>{$e->error_heading}</h1>\n";
    $trace.="<p>{$e->getMessage()}</p>\n";
    $trace.="<pre style=\"background-color: #eee;padding:10px;font-size: 11px;\">";
    $trace.="<code>{$e->getTraceAsString()}</code></pre>\n";
    $trace.="<pre style=\"background-color: #eee;padding:10px;font-size: 11px; margin-top:5px;\">";
    $trace.="<code>{$e->getFile()}\nLine: {$e->getLine()}</code></pre>\n";
    $trace.="</font>\n";
		return $trace;
	}
	public function simple_error() {
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
		$trace.="<title>Application Error</title>";
    $trace.="<h1>Application Error</h1>\n";
		return $trace;
	}
	public function email_trace($e) {
		
	}
	public function dev_giveup() {
		header("Status: 500 Application Error");
		echo $this->error_message;
		exit;
	}
	public function prod_giveup() {
		header("Status: 500 Application Error");
		echo $this->simple_error_message;
		$message=strip_tags($this->get_trace($e));
		error_log($message);
		mail("ross@webxpress.com", "Application Error on production server", $message);
		exit;
	}
	
}

class WXActiveRecordException extends WXException
{
    function __construct( $message, $code )
    {
        return parent::__construct( $message, $code );
    }
}
?>