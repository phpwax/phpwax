<?php
namespace Wax\Model;
use Wax\Core\Exception;
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class SchemaException extends Exception {
  
  public $help = "<p>You tried to write to a model in a way the defined schema does not support:</p>";
  
	function __construct( $message, $code, $model,$write_name) {
    $this->help .= "<pre>Writing to:".$write_name."</pre>";
	  $this->help .= "<pre>".print_r($model->schema("keys"), 1)."</pre>";
	  $this->help .= "<p>Check out the definitions in the ".get_class($model)." class definition and try again.</p>";
  	parent::__construct( $message, $code);
  }
}



?>
