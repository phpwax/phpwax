<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WXDependencyException extends WXException
{
	function __construct( $message, $code="Missing File Dependency" ) {
  	parent::__construct( $message, $code);
  }
}

?>