<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxDBStructureException extends WaxSqlException {
  
  public $help = "<p>Your tried to access a database property that doesn't exist. We tried syncing your database
  but it doesn't seem to have worked.
  </p><p>Check that your database models are setup correctly.</p>";
  
	function __construct( $message, $code, $query_error = false ) {
	  if($query_error) $this->help .= " <pre>$query_error</pre>";
  	parent::__construct( $message, $code);
  }
}

