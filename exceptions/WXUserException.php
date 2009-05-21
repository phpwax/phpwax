<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WXUserException extends WaxException
{
	function __construct( $message, $code="Application Error" ) {
  	parent::__construct( $message, $code);
  }
}


?>