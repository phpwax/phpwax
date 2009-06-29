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
  public $marker = '<!-- from cache -->';
  
  public function __construct($identifier, $lifetime) {
    $this->lifetime = $lifetime;
    $this->identifier = $identifier;
    $this->sub_dir = $sub_dir;
  }	
	
	public function get() {
	  if($content = $this->valid()) return $content . $this->marker;
	  else return false;
	}
	
	public function set($value) {
	  //only save cache if the file doesnt exist already - ie so the file mod time isnt always reset
	  if(!is_readable($this->identifier)) file_put_contents($this->identifier, $value); 
	}
	
	public function valid() {
	  if(!is_readable($this->identifier) ) return false;
	  $mtime = filemtime($this->identifier);
	  if(time() > $mtime + $this->lifetime){
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

