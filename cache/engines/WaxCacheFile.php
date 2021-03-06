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
  public $config = false;
  public $namespace = false;

  public function __construct($dir=false, $lifetime=3600, $suffix='cache', $identifier=false, $config=false) {

    if($config) $this->config = $config;
    if($lifetime) $this->lifetime = $lifetime;
    if($dir) $this->dir = $dir;
    else $this->dir = CACHE_DIR;
    if($identifier) $this->identifier = $identifier;
    else $this->identifier = $this->make_identifier($_SERVER['HTTP_HOST']);
    if($suffix) $this->suffix = $suffix;
    
  }

	public function get() {
	  if($content = $this->valid()) return $content;
	  else return false;
	}

	public function set($value) {
    if(!$this->identifier) $this->identifier = $this->make_identifier();
	  //only save cache if the file doesnt exist already - ie so the file mod time isnt always reset
	  if($this->identifier && !is_readable($this->identifier)){	    
	    file_put_contents($this->identifier, $value);
	    chmod($this->identifier, 0777);
    }
	}

	public function valid() {
	  if(!$this->identifier) $this->identifier = $this->make_identifier();
	  
	  if(!is_readable($this->identifier) || isset($_REQUEST['no-wax-cache'])) return false;
	  if($this->lifetime == "forever") return file_get_contents($this->identifier);
	  $mtime = filemtime($this->identifier);	  
	  if((time() - $mtime) < $this->lifetime){
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

  public function get_namespace($config, $uri){
    if(!$pos = strpos($uri, "?")) $pos = strlen($uri);
    $url = trim(substr($uri, 0, $pos), "/");
    $namespace = false;
    if($config && is_array($config) && isset($config['namespace'])){
		  $regexes = $config['namespace'];
		  while(!$namespace && count($regexes)){
		    $test = array_pop($regexes);
		    preg_match_all($test, $url, $found);
		    if(count($found) && count($found[0])) $namespace = array_shift($found[0]);
		  }
		  if($namespace) $namespace = trim($namespace, "/")."/";
		  if($namespace && !is_readable($this->dir.$namespace)){
        mkdir($this->dir.$namespace, 0777, true);
        chmod($this->dir.$namespace, 0777);
      }
    }
    return $namespace;
  }

	public function make_identifier($prefix=false){
	  if(!$prefix) $prefix=$_SERVER['HTTP_HOST'];
		$this->namespace = $namespace = $this->get_namespace($this->config, $_SERVER['REQUEST_URI']);		
		$str = $this->dir.$namespace.$prefix;
		$uri = preg_replace('/([^a-z0-9A-Z\s])/', "", $_SERVER['REQUEST_URI']);

    while(strpos($uri, "  ")) $uri = str_replace("  ", " ", $uri);
    if(strlen($uri)) $str.='-'.md5(str_replace("nowaxcache1", "", str_replace(" ", "-",$uri)));
    if(count($_GET)){
      $get = $_GET;
      unset($get['route'], $get['no-wax-cache']);
      $str .= "-g-".md5(serialize($get));
    }
    if(count($_POST)) $str .= "-p-".md5(serialize($_POST));
    return $str;
	}

}

