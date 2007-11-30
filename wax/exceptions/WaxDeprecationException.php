<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxDeprecationException extends WXException {
	function __construct( $message, $code="Application Error" ) {
  	error_log("[DEPRECATION WARNING] ".$message);
  }
}
?>