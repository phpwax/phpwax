<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxDbException extends WXException {
  
  public $help = "<p>The application couldn't initialise a database connection using the following settings:</p>";
  
	function __construct( $message, $code, $db_settings = array() ) {
	  if(!$db_settings) $this->help .= "<p>".ENV." - You have no database configured</p>";
	  else $this->help .= "<pre>".print_r($db_settings, 1)."</pre>";
	  $this->help .= "<p>Check that these settings are correctly configured and try again</p>";
  	parent::__construct( $message, $code);
  }
}



?>