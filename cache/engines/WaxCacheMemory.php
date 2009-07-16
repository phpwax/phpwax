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
	  $write_to = $this->array_path();
    $write_to[$this->keyname($this->key)] = $value;
	}
	
	public function valid() {
    $read_from = $this->array_path();
	  if(!isset($read_from[$this->key_name($this->key)]) ) return false;
	  else return $read_from[$this->key_name($this->key);
	}
	
	public function expire($query=false) {
	  if(!$query) {
	    $expire = $this->array_path();
	    if(isset($expire[$this->key])) unset($expire[$this->key]);
	  }
	}

	public function array_path() {
	  $array_path = explode("/",$this->key_path($this->key));
    $location = self::$cache;
    while($array_path) {
      $location = $write_to[array_shift($array_path)];
    }
    return $location;
	}
	

}

