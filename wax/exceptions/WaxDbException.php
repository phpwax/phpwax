<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxDbException extends WXException {
  
  public $help = "The application couldn't initialise a database connection using the following settings:";
  
	function __construct( $message, $code, $db_settings = array() ) {
	  if(!$db_settings) $this->help .= "You have no database configured";
	  else $this->help .= "<br /><pre>".print_r($db_settings, 1)."</pre>";
	  $this->help .= "<br />Check that these settings are correctly configured and try again";
  	parent::__construct( $message, $code);
  }
}



?>