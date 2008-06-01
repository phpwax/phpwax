<?php
/**
 *
 * @package PHP-Wax
 **/
 
class WaxSqlException extends WXException {
  
  public $help = "<p>There was a database query that could not execute:</p>";
  
	function __construct( $message, $code, $query_error = false ) {
	  if($query_error) $this->help .= "<pre>$query_error</pre>";
  	parent::__construct( $message, $code);
  }
}

