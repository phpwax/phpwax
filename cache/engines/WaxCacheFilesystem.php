<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to filesystem.
 *  @package PHP-Wax
 */
class WaxCacheFilesystem implements CacheEngine{
  
  public $key = false;
  public $root_dir = false;
  public $lifetime = 3600;
  public $writable = false;
  
  
  public function __construct($key, $options) {
    foreach($options as $k=>$option) $this->$k = $option;
    if(!$this->root_dir) $this->root_dir = CACHE_DIR;
    if(strpos($key, "/")) {
      $file = substr(strrchr($key, "/"),1);
      $path = strrpos($key, "/") ? substr($key, 0, strrpos($key, "/")+1) : false;
      $this->key = $file;
    } else $this->key=$key;
    $this->root_dir .= $path;
    if(!is_writable($this->root_dir)) mkdir($this->root_dir,0777,true);
    if(!is_writable($this->root_dir)) $this->writable = false;
    else $this->writable = true;
  }
	
	public function get() {
	  if($content = $this->valid()) return $content;
	  else return false;
	}
	
	public function set($value) {
	  if(!is_readable($this->key())) file_put_contents($this->key(), $value); 
	}
	
	public function valid() {
    if(!is_readable($this->key()) ) return false;
	  $mtime = filemtime($this->key());
	  if(time() > $mtime + $this->lifetime){
	    $this->expire();
	    return false;
	  }else return file_get_contents($this->key());
    
	}
	
	public function expire() {
    return unlink($this->key());
	}
	
	public function key() {
	  return $this->root_dir.$this->key;
	}

}

