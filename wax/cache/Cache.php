<?php
/**
	* @package PHP-Wax
  */

/**
 *	Class for implementing caching of data / objects etc.
 *  @package PHP-Wax
 */
class Cache {
		
	public $store = "File";
	public $engine = false;
	public $label = false;
	public $writing = false;
	
	static public $lifetime = "300";
	
	
	public function __construct($label) {
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
	  return $this->engine->valid();
	}
	
	public function expire() {
    return $this->engine->expire();
	}
	
  


}

