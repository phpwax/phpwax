<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to file.
 *  @package PHP-Wax
 */
class WaxCacheFile {
  
  public $label = false;
  public $lifetime = false;
  public $cache_dir = false;
  
  public function __construct($label, $lifetime, $store = false) {
    if(!$store) $this->cache_dir = CACHE_DIR;
    $this->label = $label;
    $this->lifetime = $lifetime;
		if(!is_readable(CACHE_DIR)) chmod(CACHE_DIR, 0777);
  }
		
	
	public function get() {
	  WaxLog::log("info", "[CACHE] Getting content from cache file for ".$this->label);
	  if(is_readable($this->file())) return file_get_contents($this->file());
		return false;
	}
	
	public function set($value) {
	  WaxLog::log("info", "[CACHE] Writing cache file for ".$this->label);
	  if(is_writable($this->file())) return file_put_contents($this->file(), $value);
	  else {
	    WaxLog::log("error", "[CACHE] Cache files could not be written. Check permissions on '".$this->cache_dir."'");
	    return false;
	  }
	}
	
	public function valid($return = false) {
	  if(!is_readable($this->file()) ) return false;
    if($return) $ret = $this->get();
	  $stats = stat($this->file());
	  if(time() > $stats["mtime"] + $this->lifetime) {
	    $this->expire();
	    if(!$return) return false;
	  }
	  if($return) return $ret;
	  else return true;
	}
	
	public function expire() {
	  WaxLog::log("info", "[CACHE] Expiring cache file for ".$this->file());
	  if(is_readable($this->file())) unlink($this->file());
	}
	
	public function file() {
	  return $this->cache_dir.Inflections::underscore(Inflections::slashcamelize($this->label)).".cache";
	}
	
  


}

