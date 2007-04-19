<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WXPermissionsException extends WXException
{
	function __construct( $message ) {
  	parent::__construct( $message, "File Permissions Error");
  }
}


?>