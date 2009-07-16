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
    $this->key = $key;
    if(!is_writable($this->file_path())) mkdir($this->file_path(),0777,true);
    if(!is_writable($this->file())) $this->writable = false;
    else $this->writable = true;
  }
	
	public function get() {
	  if($content = $this->valid()) return $content;
	  else return false;
	}
	
	public function set($value) {
	  if(!is_readable($this->file())) file_put_contents($this->file(), $value); 
	}
	
	public function valid() {
    if(!is_readable($this->file()) ) return false;
	  $mtime = filemtime($this->file());
	  if(time() > $mtime + $this->lifetime){
	    $this->expire();
	    return false;
	  }else return file_get_contents($this->file());
    
	}
	
	public function expire($query = false) {
    if(!$query) return unlink($this->file());
    else $this->flush($query);
	}
	
	
	public function file() {
	  return $this->root_dir.$this->key;
	}
	
	public function file_path() {
	  return $this->root_dir.$this->key_path($this->key);
	}
	
	public function flush($query) {
	  if(strlen($query)>1) {
	    if(is_dir($this->root_dir.$query)) {
	      File::recursively_delete($this->root_dir.$query);
	    }
	  } else foreach(scandir($this->root_dir) as $dir) File::recursively_delete($this->root_dir.$dir);
	}
	
	public function key_path($full_key) {
	  if(strpos($full_key, "/")) {
      $path = substr($full_key, 0, strrpos($full_key, "/")+1);
      return $path;
    } else return "";
	}
	
	public function key_name($full_key) {
	  if(strpos($full_key, "/")) {
      return substr(strrchr($full_key, "/"),1);
    } else return $full_key;
	}
	

}

