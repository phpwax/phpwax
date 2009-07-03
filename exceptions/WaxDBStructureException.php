<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxDBStructureException extends WaxSqlException {
  
  public $help = "<p>Your database schema is not up to date: Maybe running script/syncdb will fix.</p>";
  
	function __construct( $message, $code, $query_error = false ) {
	  if($query_error) $this->help .= " <pre>$query_error</pre>";
  	parent::__construct( $message, $code);
  }
}

