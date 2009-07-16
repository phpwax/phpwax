<?php
/**
	* @package PHP-Wax
  */

/**
 *	Class for implementing caching of data / objects etc.
 *  @package PHP-Wax
 */
class WaxCache {
		
	public $store = "File";
	public $engine = false;
	public $enabled = true;
	public $lifetime = 3600;
	
	
	
	public function __construct($label, $store=false, $options = array()) {
	  $this->init();
	  if(is_string($store)) {
	    if($store) $this->store=ucfirst($store);
	    if($this->store == "default") $this->store= ucfirst(Config::get("cache_engine"));
	  }
	  if($this->store == "File") $this->store="Filesystem";
	  if(is_array($store)) $options = $store;
	  $class = "WaxCache".$this->store;
	  $this->engine = new $class($label, $options);
	}
	
	public function get() {
    return $this->engine->get();
	}
	
	public function set($value) {
    return $this->engine->set($value);
	}
	
	public function valid($return = false) {
	  if(!$this->enabled) return false;
	  return $this->engine->valid($return);
	}
	
	public function expire() {
    return $this->engine->expire();
	}
	
	public function flush($query = false) {
	  return $this->engine->flush($query);
	}
	
	public function init() {
	  if(Config::get("cache") == "off") $this->enabled=false;
	  if($engine = Config::get("cache_engine")) $this->store=ucfirst($engine);
	}
	
	public function set_key($key) {
	  $this->engine->key = $key;
	}
	
	public function key_path($full_key) {
	  if(strpos($full_key, "/")) {
      $path = substr($full_key, 0, strrpos($full_key, "/")+1);
      return $path;
    } else return "";
	}
	
	public function keyname($full_key) {
	  if(strpos($full_key, "/")) {
      return substr(strrchr($full_key, "/"),1);
    } else return $full_key;
	}
	
  


}

?>