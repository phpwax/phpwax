<?php
/**
	* @package PHP-Wax
  */

/**
 *	Wrapper class for accessing request variables.
 *  @package PHP-Wax
 */
class Request {
		
	
	
	public function filter($name, $raw) {
	  if(!$raw) $val = filter_input(INPUT_GET | INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS);
    else if($val = filter_input(INPUT_GET | INPUT_POST, $name, FILTER_UNSAFE_RAW));
	  return $val;
	}
	
	public function raw($name) {
	  return self::filter($name, true);
	}
	
	public function get($name) {
	  return self::filter($name, false);
	}


}

