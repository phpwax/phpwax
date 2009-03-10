<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WXPermissionsException extends WXException
{
	function __construct( $message, $file = false ) {
	  if($file) $this->help .= " <pre>$file</pre>";
  	parent::__construct( $message, "File Permissions Error");
  }
}

