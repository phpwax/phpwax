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
	    elseif($val = filter_input(INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS));
	    else $val = false;
    } else {
	    if($val = filter_input(INPUT_GET, $name, FILTER_UNSAFE_RAW));
	    elseif($val = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW));
	    else $val = false;   
	  }
	  return $val;
	}
	
	public function raw($name) {
	  return self::filter($name, true);
	}
	
	public function get($name) {
	  return self::filter($name, false);
	}


}

