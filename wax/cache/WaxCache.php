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
	public $label = false;
	public $writing = false;
	public $enabled = true;
	
	static public $lifetime = "300";
	
	
	public function __construct($label) {
	  $this->init();
	  $this->label = $label;
	  $class = "WaxCache".$this->store;
	  $this->engine = new $class($label, self::$lifetime);
	}
	
	public function get() {
    return $this->engine->get();
	}
	
	public function set($value) {
    return $this->engine->set($value);
	}
	
	public function valid() {
	  if(!$this->enabled) return false;
	  return $this->engine->valid();
	}
	
	public function expire() {
    return $this->engine->expire();
	}
	
	public function init() {
	  if($en = Config::get("cache") && $en="off") $this->enabled=false;
	  if($store = Config::get("cache_store")) $this->store=$store;
	}
	
  


}

