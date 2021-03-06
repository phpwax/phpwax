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
  public $main_suffix = '.cache';
  public $source=false;
	public $meta_suffix ='--META--';
	public $lock_suffix ='--LOCK--';

	public function set($value) {
		$this->set_meta();
	  //only save cache if the file doesnt exist already - ie so the file mod time isnt always reset
	  if($this->identifier && !is_readable($this->identifier.$this->main_suffix)) file_put_contents($this->identifier.$this->main_suffix, $value);
		chmod($this->identifier.$this->main_suffix, 0777);
	}

	public function valid() {
	  if(!$this->identifier) $this->identifier = $this->make_identifier();
	  
	  if(!is_readable($this->identifier.$this->main_suffix) || isset($_GET['no-wax-cache'])) return false;
		$return = file_get_contents($this->identifier.$this->main_suffix);
		$meta = $this->get_meta();
	  $age = time() - filemtime($this->identifier.$this->main_suffix);
		if(($age > $this->lifetime) && !isset($_GET['no-wax-cache']) && !$this->locked() && $this->lifetime != "forever"){
			$cmd = "php ".dirname(__FILE__)."/WaxRegenFileCache.php ".$this->identifier.$this->meta_suffix." > /dev/null &";
			exec(escapeshellcmd($cmd));
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
		file_put_contents($this->identifier.$this->meta_suffix, serialize(array('ident'=>$this->identifier.$this->main_suffix,'location'=>"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'time'=>time(), 'post'=>serialize($_POST), 'lock'=>$this->identifier.$this->lock_suffix) ));
		chmod($this->identifier.$this->meta_suffix, 0777);
	}




}

?>