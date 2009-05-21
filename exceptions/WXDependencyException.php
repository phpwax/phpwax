<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WXDependencyException extends WaxException
{
	function __construct( $message, $code="Missing File Dependency" ) {
  	parent::__construct( $message, $code);
  }
}

?>