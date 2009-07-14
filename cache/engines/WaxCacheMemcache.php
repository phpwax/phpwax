<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to memcache.
 *  @package PHP-Wax
 */
class WaxCacheMemcache implements CacheEngine{
  
  public $key = false;
  public $server = "localhost";
  public $port = "11211";
  public $memcache = false;
  public $lifetime = 3600;
  
  
  public function __construct($key) {
    $this->key = $key;
    $this->memcache = new Memcache;
    $this->memcache->connect($this->server, $this->port) or $this->memcache = false;
  }
	
	public function get() {
	  if($content = $this->valid()) return $content;
	  else return false;
	}
	
	public function set($value) {
    $this->memcache->set($this->key, $value, 0,$this->lifetime);
	}
	
	public function valid() {
	  return $this->memcache->get($this->key);
	}
	
	public function expire() {
	  $this->memcache->delete($this->key);
	}

}

