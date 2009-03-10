<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WXActiveRecordException extends WXException {
  
  
	function __construct( $message ) {
  	return parent::__construct( $message, "Database Error");
  }
}



?>