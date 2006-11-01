<?php

class WXPermissionsException extends WXException
{
	function __construct( $message ) {
  	parent::__construct( $message, "File Permissions Error");
  }
}


?>