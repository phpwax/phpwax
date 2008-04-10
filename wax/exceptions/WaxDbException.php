<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxDbException extends WXException {
  
  public $help = "The application couldn't initialise a database connection using the following settings:";
  
	function __construct( $message, $code, $db_settings = array() ) {
	  $this->help .= "<br /><pre>".syck_dump($db_settings)."</pre>";
  	parent::__construct( $message, $code);
  }
}



?>