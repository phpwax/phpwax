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
	static $get = false;
	static $post = false;
	
	public function filter($val) {
	  return filter_var($val, FILTER_SANITIZE_STRING);
	}
	
	
	public function get($name) {
	  if(!self::$get) self::$get = WaxUrl::get_params();
	  return self::$get[$name];
	}
	
	public function post($name) {
	  if(!self::$post) self::$post = $_POST;
	  return self::$post[$name];
	}
	
	public function safe_get($name) {
	  return self::filter(self::get($name));
	}
	
	public function safe_post($name) {
	  return self::filter(self::post($name));
	}


}

