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
	public $config = array();
	public $label = "";
	
	
	
	public function __construct($label=false, $store=false, $options = array()) {
	  $this->label = $label;
	  $this->compat();
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
	  if(!$this->config["enabled"]) return false;
    return $this->engine->get();
	}
	
	public function set($value) {
	  if(!$this->config["enabled"]) return false;
    return $this->engine->set($value);
	}
	
	public function valid($return = false) {
	  if(!$this->config["enabled"]) return false;
	  if(!$this->enabled) return false;
	  return $this->engine->valid($return);
	}
	
	public function expire($query=false) {
	  if(!$this->config["enabled"]) return false;
    return $this->engine->expire($query);
	}

	
	public function init() {
	  if($ns = $this->get_namespace()) {
	    if(is_array($conf = Config::get("cache/".$ns))) $this->config = $conf;
	  }
	  else $this->config = Config::get("cache");
	  if(!$this->config["enabled"]) $this->enabled=false;
	  if($engine = $this->config["engine"]) $this->store=ucfirst($engine);
	  elseif($engine = Config::get("cache/engine")) $this->store=ucfirst($engine);
	}
	
	public function set_key($key) {
	  $this->label = $key;
	  $this->engine->key = $key;
	  $this->init();
	}
	
	public function get_key() {
	  return $this->engine->key;
	}
	
	public function get_namespace() {
	  return substr($this->label,0,strpos($this->label,"/"));
	}
	
	public function compat() {
	  if($layout = Config::get("layout_cache")) Config::set("cache/layout", $layout);
	  if($part = Config::get("partial_cache")) Config::set("cache/partial", $part);
	  if($img = Config::get("image_cache")) Config::set("cache/image", $img);
	}
	  


}

?>