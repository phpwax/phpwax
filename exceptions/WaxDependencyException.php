<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxDependencyException extends WaxException
{
	function __construct( $message, $code="Missing File Dependency" ) {
  	parent::__construct( $message, $code);
  }
}

?>