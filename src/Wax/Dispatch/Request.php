<?php
namespace Wax\Dispatch;

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
	
	
	public function get($name, $clean=false) {
	  self::init();
	  if($clean) {
	    $vals = filter_var_array(self::$get, FILTER_SANITIZE_SPECIAL_CHARS);
	    return $vals[$name];
	  }
	  return self::$get[$name];
	}
	
	public function post($name, $clean=false) {
	  self::init();
	  if($clean) {
	    $vals = filter_var_array(self::$post, FILTER_SANITIZE_SPECIAL_CHARS);
	    return $vals[$name];
	  }
	  return self::$post[$name];
	}
	
	public function param($name, $clean = false) {
	  self::init();
	  return self::$params[$name];
	}
	


}

