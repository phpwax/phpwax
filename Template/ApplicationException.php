<?php
namespace Wax\Template;
use Wax\Core\Exception;

/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class ApplicationException extends Exception
{
	function __construct( $message, $code="Application Error" ) {
  	parent::__construct( $message, $code);
  }
}


