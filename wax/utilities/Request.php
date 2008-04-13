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
	static $init = false;
	
	public function filter($val) {
	  return filter_var($val, FILTER_FLAG_ENCODE_HIGH);
	}
	
	public function init() {
	  if(!self::$init) {
	    self::$get = WaxUrl::get_params();
	    self::$post = $_POST;
	    self::$params = array_merge(self::$get,self::$post);
	    self::$init = true;
	  }
	}
	
	
	public function get($name) {
	  self::init();
	  return self::$get[$name];
	}
	
	public function post($name) {
	  self::init();
	  return self::$post[$name];
	}
	
	public function param($name) {
	  self::init();
	  return self::$params[$name];
	}


}

