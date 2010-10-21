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
  public $marker = '';
  public $suffix = 'cache';
  
  public function __construct($dir=false, $lifetime=3600, $suffix='cache', $identifier=false) {
    if($lifetime) $this->lifetime = $lifetime;
    if($dir) $this->dir = $dir;
    else $this->dir = CACHE_DIR;
    if($identifier) $this->identifier = $identifier;
    else $this->indentifier = $this->make_identifier($_SERVER['HTTP_HOST']);
    if($suffix) $this->suffix = $suffix;
  }
	
	public function get() {
	  if($content = $this->valid()) return $content . $this->marker;
	  else return false;
	}
	
	public function set($value) {
	  //only save cache if the file doesnt exist already - ie so the file mod time isnt always reset
	  if($this->identifier && !is_readable($this->identifier)) file_put_contents($this->identifier, $value); 
	}
	
	public function valid() {
	  if(!is_readable($this->identifier) ) return false;
	  if($this->lifetime == "forever") return file_get_contents($this->identifier);
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
	  $str = $this->dir.$prefix;
	  $sess = $_SESSION[Session::get_hash()];
		unset($sess['referrer']);
		$uri = preg_replace('/([^a-z0-9A-Z\s])/', "", $_SERVER['REQUEST_URI']);
    while(strpos($uri, "  ")) $uri = str_replace("  ", " ", $uri);
    if(strlen($uri)) $str.='-'.md5(str_replace("nowaxcache1", "", str_replace(" ", "-",$uri)));

    if(count($sess)) $str .= "-s-".md5(serialize($sess));
    if(count($_GET)){
      $get = $_GET;
      unset($get['route'], $get['no-wax-cache']);
      $str .= "-g-".md5(serialize($get));
    }
    if(count($_POST)) $str .= "-p-".md5(serialize($_POST));
    return $str;
	}

}

