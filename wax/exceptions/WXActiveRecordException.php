<?php

class WXActiveRecordException extends WXException
{
	function __construct( $message ) {
  	return parent::__construct( $message, "Database Error");
  }
}



?>