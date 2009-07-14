<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to memory.
 *  @package PHP-Wax
 */
class WaxCacheMemory implements CacheEngine{
  
  public $key = false;
  static public $cache = array();
  
  public function __construct($key) {
    $this->key = $key;
  }
	
	public function get() {
	  if($content = $this->valid()) return $content;
	  else return false;
	}
	
	public function set($value) {
    self::$cache[$this->key] = $value;
	}
	
	public function valid() {
	  if(!isset(self::$cache[$this->key]) ) return false;
	  else return self::$cache[$key];
	}
	
	public function expire() {
	  if(isset(self::$cache[$this->key])) unset(self::$cache[$this->key]);
	}

}

