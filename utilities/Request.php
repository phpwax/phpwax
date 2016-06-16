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
	
	public static function filter($val) {
	  return filter_var($val, FILTER_FLAG_ENCODE_HIGH);
	}
	
	public static function init() {
	  if(!self::$init) {
	    self::$get = WaxUrl::get_params();
	    self::$post = $_POST;
	    self::$params = array_merge(self::$get,self::$post);
	    self::$init = true;
	  }
	}


	public static function get($name, $clean = false)
	{
		self::init();
		if ($clean) {
			$vals = filter_var_array(self::$get, FILTER_SANITIZE_SPECIAL_CHARS);

			return $vals[$name];
		}
		if (isset(self::$get[$name])) {
			return self::$get[$name];
		}

	}
	
	public static function post($name, $clean=false) {
	  self::init();
	  if($clean) {
	    $vals = filter_var_array(self::$post, FILTER_SANITIZE_SPECIAL_CHARS);
	    return $vals[$name];
	  }
	  return self::$post[$name];
	}
	
	public static function param($name, $clean = false) {
	  self::init();
	  return self::$params[$name];
	}
	


}

