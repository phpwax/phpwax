<?php
namespace Wax\Cache;

/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to memory.
 *  @package PHP-Wax
 */
class Memory implements Engine{
  
  public $key = false;
  static public $cache = array();
  
  public function __construct($key, $options=array()) {
    $this->key = $key;
  }
	
	public function get() {
	  if($content = $this->valid()) return $content;
	  else return false;
	}
	
	public function set($value) {
	  $write_to = &$this->array_path();
    $write_to[$this->key_name($this->key)] = $value;
	}
	
	public function valid() {
    $read_from = &$this->array_path();
	  if(!isset($read_from[$this->key_name($this->key)]) ) return false;
	  else return $read_from[$this->key_name($this->key)];
	}
	
	public function expire($query=false) {
	  if(!$query) {
	    $expire = &$this->array_path();
	    if(isset($expire[$this->key])) unset($expire[$this->key]);
	  }
	}

	public function &array_path() {
	  $array_path = explode("/",trim($this->key_path($this->key),"/"));
   $location = &self::$cache;
    while($array_path) {
      $location = &$location[array_shift($array_path)];
      $location = array();
    }
    return $location;
	}
	
	public function key_path($full_key) {
	  if(strpos($full_key, "/")) {
      $path = substr($full_key, 0, strrpos($full_key, "/")+1);
      return $path;
    } else return "";
	}
	
	public function key_name($full_key) {
	  if(strpos($full_key, "/")) {
      return substr(strrchr($full_key, "/"),1);
    } else return $full_key;
	}
	

}

