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
	public $lock_suffix = "-lock-";	

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
		if(!$connected = $this->memcache->connect($this->server, $this->port)) $this->memcache = false;
  }
	
	public function get() {
	  if($this->memcache && ($content = $this->valid()) ) return $content;
		else return false;
	}
	
	public function set($value) {		
	  if($this->memcache){
			$this->memcache->set($this->identifier, $value, 0, 0);
			$this->set_meta();
		}
	}
	
	public function locked(){
		if($this->memcache && $this->memcache->get($this->identifier.$this->lock_suffix)) return true;
		else return false;
	}
	
	public function set_meta(){
		$data = array('lock'=>$this->identifier.$this->lock_suffix, 'ident'=>$this->identifier,'location'=>"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'time'=>time(), 'post'=>serialize($_POST));
		if($this->memcache) $this->memcache->set($this->identifier.$this->meta_suffix, serialize($data), 0, 0);
	}
	public function get_meta(){
		if($this->memcache) return unserialize($this->memcache->get($this->identifier.$this->meta_suffix));
		else return array(); 
	}
	
	public function valid() {
	  if(!$this->memcache || $_GET['no-wax-cache']) return false;
		$return = $this->memcache->get($this->identifier);
		$meta = $this->get_meta();
	  $age = time() - $meta['time'];
		if(($age > $this->lifetime) && !$_GET['no-wax-cache'] && !$this->locked()){
			$cmd = "php ".dirname(__FILE__)."/WaxRegenMemcacheCache.php ".$this->identifier.$this->meta_suffix." {$this->server} {$this->port} &";
			exec($cmd, $output, $result);
		}
		return $return;
	}
	
	public function expire() {
	  if($this->identifier && $this->memcache) $this->memcache->delete($this->identifier);
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

