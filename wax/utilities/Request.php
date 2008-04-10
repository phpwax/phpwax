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
	  if(!$raw) {
	    if($val = filter_input(INPUT_GET, $name, FILTER_SANITIZE_SPECIAL_CHARS));
	    if($val = filter_input(INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS));
    } else {
	    if($val = filter_input(INPUT_GET, $name, FILTER_UNSAFE_RAW));
	    if($val = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW));    
	  }
	}
	
	public function raw($name) {
	  return self::$filter($name, true);
	}
	
	public function get($name) {
	  return self::$filter($name, false);
	}


}

