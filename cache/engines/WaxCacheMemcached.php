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
  public static $connection = false;
  
  public function __construct($key, $options=array()) {
    $this->key = $key;
    $this->memcache = new Memcached();
    foreach($options as $k=>$option) $this->$k = $option;
    try {
      set_error_handler(function(){
        WaxCacheMemcached::$connection = false;
      });
      $con = $this->memcache->addServer($this->server, $this->port);
      if($con) self::$connection = true;
      set_error_handler('throw_wxerror', 247 );
    } catch (Exception $e) {}
  }
  
	
	public function get() {
	  //if(!self::$connection) return false;
	  if($this->is_namespaced()) return $this->memcache->get($this->namespaced_key());
	  return $this->memcache->get($this->key);
	}
	
	public function set($value) {
	  //if(!self::$connection) return false;
	  	if($this->is_namespaced()) return $this->memcache->set($this->namespaced_key(), $value, $this->lifetime);
    	else return $this->memcache->set($this->key, $value, $this->lifetime);
	}
	
	public function expire($query=false) {
	  if(!$query) $this->memcache->delete($this->key, 0);
	  else $this->memcache->increment($query);
	}
	public function expire_namespace($key){
	  $key = $this->namespaced_key();
	  $this->expire($key);
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
	
	public function is_namespaced() {
	  return strlen($this->key_path($this->key));
	}
	
	public function namespaced_key() {
	  $ns_key = $this->memcache->get($this->key_path($this->key)); 
    // if not set, initialize it 
    if($ns_key===false) $this->memcache->set($this->key_path($this->key), rand(1, 10000)); 
    return $ns_key."_".$this->key_name($this->key);
	}

}

