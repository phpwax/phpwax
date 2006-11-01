<?php

class WXDependencyException extends WXException
{
	function __construct( $message, $code="Missing File Dependency" ) {
  	parent::__construct( $message, $code);
  }
}

?>