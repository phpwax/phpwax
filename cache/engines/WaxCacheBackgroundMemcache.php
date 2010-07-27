<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to file.
 *  @package PHP-Wax
 */
class WaxCacheBackgroundMemcache implements CacheEngine{

  public $identifier = false;
  public $lifetime = 100;  
  public $dir = false;
  public $marker = '';
  public $suffix = 'cache';
	public $meta_suffix = "-meta-";

	public $server = "localhost";
  public $port = "11211";
  public $memcache = false;
  
  public function __construct($prefix=false, $lifetime=0, $suffix='cache', $identifier=false) {
		$this->lifetime = $lifetime; 
    if($prefix) $this->prefix = $dir;
    else $this->prefix = 'mc-';
    if($identifier) $this->identifier = $identifier;
    else $this->indentifier = $this->make_identifier($_SERVER['HTTP_HOST']);
    if($suffix) $this->suffix = $suffix;
		$this->memcache = new Memcache;
		$this->memcache->connect($this->server, $this->port) or $this->memcache = false;
  }
	
	public function get() {
	  return unserialize($this->memcache->get($this->identifier));
	}
	
	public function set($value) {
		$this->set_meta();
	  $this->memcache->set($this->identifier, $value, 0, 0);
	}
	public function set_meta(){
		$data = array('ident'=>$this->identifier,'location'=>"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'time'=>time(), 'post'=>serialize($_POST));
		$this->memcache->set($this->identifier.$this->meta_suffix, serialize($data), 0, 0);
	}
	public function get_meta(){
		return $this->memcache->get($this->identifier.$this->meta_suffix);
	}
	
	public function valid() {
	  if((!$return = $this->get()) || $_GET['no-wax-cache']) return false;
		$meta = $this->get_meta();
	  $age = time() - $meta['time'];
		if(($age > $this->lifetime) && !$_GET['no-wax-cache']){
			$cmd = "php ".dirname(__FILE__)."/WaxRegenMemcacheCache.php ".$this->identifier.$this->meta_suffix." {$this->server} {$this->port} &";
			exec($cmd, $output, $result);
		}
		return $return;
	}
	
	public function expire() {
	  if($this->identifier) $this->memcache->delete($this->identifier);
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
    if(strlen($uri)) $str.='-'.str_replace("nowaxcache1", "", str_replace(" ", "-",$uri));

    if(count($data)) $str .= "-data-".md5(serialize($data));
    if(count($_GET)){
      $get = $_GET;
      unset($get['route'], $get['no-wax-cache']);
      $str .= "-get-".md5(serialize($get));
    }
    if(count($_POST)) $str .= "-post-".md5(serialize($_POST));
    return $str;
	}

}

