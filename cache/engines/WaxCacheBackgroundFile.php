<?php
/**
	* @package PHP-Wax
  */

/**
 *	Engine for caching of data / objects to file.
 *  @package PHP-Wax
 */
class WaxCacheBackgroundFile extends WaxCacheFile implements CacheEngine{

  public $identifier = false;
  public $lifetime = false;
  public $dir = false;
  public $marker = '';
  public $suffix = 'cache';
  public $source=false;
	public $meta_suffix ='--META--';
	public $lock_suffix ='--LOCK--';

	public function set($value) {
		$this->set_meta();
	  //only save cache if the file doesnt exist already - ie so the file mod time isnt always reset
	  if($this->identifier && !is_readable($this->identifier)) file_put_contents($this->identifier, $value);
		chmod($this->identifier, 0777);
	}

	public function valid() {
	  if(!is_readable($this->identifier) || $_GET['no-wax-cache']) return false;
		$return = file_get_contents($this->identifier);
		$meta = $this->get_meta();
	  $age = time() - $meta['time'];
		if(($age > $this->lifetime) && !$_GET['no-wax-cache'] && !$this->locked()){
			$cmd = "php ".dirname(__FILE__)."/WaxRegenFileCache.php ".$this->identifier.$this->meta_suffix." &";
			exec($cmd, $output, $result);
		}
		return $return;
	}
	
	public function locked(){
		if(is_readable($this->identifier.$this->lock_suffix)) return true;
		else return false;
	}

	public function get_meta(){
		if(is_readable($this->identifier.$this->meta_suffix)) return unserialize(file_get_contents($this->identifier.$this->meta_suffix));
	}
	public function set_meta(){
		file_put_contents($this->identifier.$this->meta_suffix, serialize(array('ident'=>$this->identifier,'location'=>"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'time'=>time(), 'post'=>serialize($_POST), 'lock'=>$this->identifier.$this->lock_suffix) ));
		chmod($this->identifier.$this->meta_suffix, 0777);
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

