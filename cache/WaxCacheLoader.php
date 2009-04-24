<?php
/**
	* @package PHP-Wax
  */

/**
 *	Class for loading in pre existing cache files based on the current location or value passed in
 *  @package PHP-Wax
 */
class WaxCacheLoader {
		
	public static $sub_directory = '';
	public static $life_time = 600;
	public static $cache_file = false;
	public static $cache_type = 'layout';
	public static $file_suffix = 'cache';
	public static $clear_on_post = true;
		
	public static function valid($file=false){
		if(!$file) $file = self::file_name(); //fetch the file name if none passed	
		if($file && !count($_POST) ){ //if post data the cache isnt valid
			$mtime = filemtime($file);
			$diff = time() - $mtime;
			if($diff < self::$life_time) return true; //if within time limit return true
		}elseif(count($_POST) && self::$clear_on_post) self::expire(); //if post data then clear out the cache
		return false;
	}

	public static function get($file=false){
		if(self::valid($file)) return file_get_contents(self::$cache_file);
		else return false;
	}
	
	public static function expire($file=false){
		if($file && is_readable($file)) unlink($file);
		else{
			foreach(glob(CACHE_DIR. self::$sub_directory . str_replace("-", "_",$_SERVER['HTTP_HOST']).'*.'.self::$file_suffix) as $file ) unlink($file);
		}
	}

	public static function file_name(){
		if(!self::$cache_file){
			$sess = $_SESSION[Session::get_hash()];
			unset($sess['referrer']);
			$path = $_SERVER['REQUEST_URI'].serialize($_GET).serialize($sess);
			self::$cache_file = CACHE_DIR . self::$sub_directory . str_replace("-", "_",$_SERVER['HTTP_HOST']). md5($path) . '.'.self::$cache_type . '.'.self::$file_suffix;
		}
		if(is_readable(self::$cache_file)) return self::$cache_file;
		else return false;
	}
}

?>