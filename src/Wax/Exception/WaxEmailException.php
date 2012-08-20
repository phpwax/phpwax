<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxEmailException extends WaxException {
  public $help = "<p>The raw email data is detailed below:</p>";
  
	function __construct( $message, $help = "") {
	  $this->help.="<pre>".$help."</pre>";
  	parent::__construct( $message, "Email Send Error");
  }
}

?>