<?php
/**
	* @package PHP-Wax
  */

/**
 *	Wrapper class for accessing request variables.
 *  @package PHP-Wax
 */
class Request {
		
	static $params = false;
	
	public function filter($name, $raw) {
	  if(!$raw) $val = filter_var(self::$params[$name], FILTER_SANITIZE_SPECIAL_CHARS);
    else      $val = filter_var(self::$params[$name], FILTER_UNSAFE_RAW);
	  return $val;
	}
	
	public function raw($name) {
	  return self::filter($name, true);
	}
	
	public function get($name) {
	  if(!self::$params) self::$params = array_merge(WaxUrl::get_params(), $_POST);
	  return self::filter($name, false);
	}


}

