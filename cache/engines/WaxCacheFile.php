<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to file.
 *  @package PHP-Wax
 */
class WaxCacheFile implements CacheEngine{
  
  public $identifier = false;
  public $lifetime = false;  
  public $dir = false;
  public $marker = '<!-- CACHED -->';
  public $suffix = 'cache';
  
  public function __construct($dir=false, $lifetime=false, $suffix='cache', $identifier=false) {
    if($lifetime) $this->lifetime = $lifetime;
    
    if($dir) $this->dir = $dir;
    else $this->dir = CACHE_DIR;
    
    if($identifier) $this->identifier = $identifier;
    else $this->indentifier = $this->make_identifier($_SERVER['HTTP_HOST']);
    $this->suffix = $suffix;
    $this->dir = $dir;
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
	
	public function make_identifier($prefix=false){
	  if(!$prefix) $prefix=$_SERVER['HTTP_HOST'];
	  $str .= $this->dir.$prefix;
	  $sess = $_SESSION[Session::get_hash()];
		unset($sess['referrer']);
		$uri = preg_replace('/([^a-z0-9A-Z\s])/', "", $_SERVER['REQUEST_URI']);
    while(strpos($uri, "  ")) $uri = str_replace("  ", " ", $uri);
    if(strlen($uri)) $str.='-'.str_replace(" ", "-",$uri);    
	  
    if(count($data)) $str .= "-data-".serialize($data);
    if(count($_GET)){
      $get = $_GET;
      unset($get['route']);
      $str .= "-get-".serialize($get);
    }
    if(count($_POST)) $str .= "-post-".serialize($_POST);      
    return $str.'.'.$this->suffix;
	}

}

