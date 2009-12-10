<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxUserException extends WaxException
{
	function __construct( $message, $code="Application Error" ) {
  	parent::__construct( $message, $code);
  }
}


?>