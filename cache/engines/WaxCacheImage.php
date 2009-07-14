<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to file.
 *  @package PHP-Wax
 */
class WaxCacheImage extends WaxCacheFile implements CacheEngine{
  
  public $identifier = false;
  public $lifetime = false;  
  public $dir = false;
  public $marker = '';
  public $suffix = '';  
  	
  public function __construct($dir=false, $lifetime=false, $suffix='cache', $identifier=false) {
    if($lifetime) $this->lifetime = $lifetime;    
    if($dir) $this->dir = $dir;
    else $this->dir = CACHE_DIR;    
    if($identifier) $this->identifier = $identifier;
    else $this->identifier = $this->make_identifier($_SERVER['HTTP_HOST']);
    $this->suffix = $suffix;
    $this->dir = $dir;
  }	
  	
	public function get(){
    return $this->valid();
	}
	
	public function valid() {
	  if(!is_readable($this->identifier) ) return false;
	  $mtime = filemtime($this->identifier);
	  if(time() > $mtime + $this->lifetime){
	    $this->expire();
	    return false;
	  }else return true;
	}
	
	public function make_identifier($prefix=false){
	  if(!$this->indentifier){
	    $details = explode("/", ltrim($_SERVER['REQUEST_URI'],"/show_image"));
      return $this->dir. $details[0].'_'.$details[1];    
    }else return $this->indentifier;
	}


}

