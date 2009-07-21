<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to file.
 *  @package PHP-Wax
 */
class WaxCacheImage extends WaxCacheFilesystem implements CacheEngine{
  
  public $identifier = false;
  public $size = "100";
  public $lifetime = 10000;
  	
  public function __construct($key, $options) {
    $this->key = $this->key_name();
    parent::__construct($this->key, $options);
  }	
  	
	public function get(){
    File::display_image($this->file());
	}
	
	public function set($value) {
	  if(!$this->source) return false;
	  return File::resize_image($this->source, $this->file(), $this->size);
	}
	
	
	
	public function key_name(){
	  if(!$this->indentifier){
	    $details = explode("/", ltrim($_SERVER['REQUEST_URI'],"/show_image"));
      return "images/".$details[0].'_'.$details[1];    
    }else return $this->indentifier;
	}


}

