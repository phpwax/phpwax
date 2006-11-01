<?php

class WXEmailException extends WXException
{
	function __construct( $message ) {
  	parent::__construct( $message, "Email Send Error");
  }
}

?>