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
	
	static public $lifetime = 3600;
	
	
	public function __construct($label, $options = array()) {
	  $this->init();
	  foreach($options as $k=>$option) $this->$k=$option; 
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
	
	public function valid($return = false) {
	  if(!$this->enabled) return false;
	  return $this->engine->valid($return);
	}
	
	public function expire() {
    return $this->engine->expire();
	}
	
	public function init() {
	  if(Config::get("cache") == "off") $this->enabled=false;
	  if($engine = Config::get("cache_engine")) $this->store=ucfirst($engine);
	}
	
  


}

?>