<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to file.
 *  @package PHP-Wax
 */
class WaxCacheFile {
  
  public $identifier = false;
  public $lifetime = false;  
  public $sub_dir = false;
  
  public function __construct($identifier, $lifetime) {
    $this->lifetime = $lifetime;
    $this->identifier = $identifier;
    $this->sub_dir = $sub_dir;
  }	
	
	public function get() {
	  return $this->valid() . "<!-- from cache -->";
	}
	
	public function set($value) {
	  file_put_contents($this->identifier, $value);
	}
	
	public function valid() {
	  if(!is_readable($this->identifier) ) return false;
	  $stats = stat($this->identifier);
	  if(time() > $stats["mtime"] + $this->lifetime){
	    $this->expire();
	    return false;
	  }else return file_get_contents($this->identifier);
	}
	
	public function expire() {
	  if(is_readable($this->identifier)) unlink($this->identifier);
	}
	
	public function file() {
	  return $this->identifier;
	}
	
  


}

