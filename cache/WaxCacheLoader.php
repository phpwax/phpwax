<?php
/**
	* @package PHP-Wax
  */

/**
 *	Class for loading in pre existing cache files based on the current location or value passed in
 *  @package PHP-Wax
 */
class WaxCacheLoader {

	public $config=array();
	public $engine_type = 'File';
	public $engine=false;
	public $suffix = 'cache';
  public $identifier = false;
  public $enabled= true;


  public function __construct($config=false, $dir="", $old=false, $format='html'){
    if(is_array($config)) foreach($config as $k=>$v) $this->config[$k] = $v;
    elseif(is_string($config)) $this->config['engine'] = $this->engine_type = $config;
    
    if($dir) $this->config['dir'] = $dir;
    else $this->config['dir'] = CACHE_DIR;
    if($this->config['engine']) $this->engine_type = $this->config['engine'];
    if(!is_readable($this->config['dir'])) mkdir($this->config['dir'], 0777, true);    
    
    if($this->config['lifetimes'] && ($lt = $this->lifetimes($this->config['lifetimes'])) ) $this->lifetime = $lt;
    elseif($this->config['lifetime']) $this->lifetime = $this->config['lifetime'];
    else $this->lifetime = 3600;
  }

	public function identifier(){
	  if(!$this->identifier){
		  $class = 'WaxCache'.$this->engine_type;
      if(!$this->engine) $this->engine = new $class($this->config['dir'], $this->lifetime, $this->suffix, false, $this->config);
      $this->identifier =  $this->engine->identifier = $this->engine->make_identifier();
    }
    return $this->identifier;
	}

  public function excluded($config, $match=false){
    if(!$match) $match = $_SERVER['REQUEST_URI'];
    if($config['exclude_post'] == "yes" && count($_POST)) return true;
    if(isset($config['exclusions'])){
      $excluded = $config['exclusions'];
      $all_matches = array();
	    if(is_array($excluded)){
	      foreach($excluded as $name => $regex){
	        preg_match_all($regex, $match, $matches);
	        if(count($matches[0])) $all_matches = array_merge($all_matches, $matches);
	      }
	    }else preg_match_all($excluded, $match, $all_matches);
	    if(count($all_matches)) return true;
    }
    return false;
  }

  public function included($config, $match=false){
    if(!$match) $match = $_SERVER['REQUEST_URI'];
    if($config['exclude_post'] == "yes" && count($_POST)) return false;
    if(isset($config['inclusions'])){
      $included = $config['inclusions'];
      $all_matches = array();
	    if(!is_array($included)) $included = array($included);
	    foreach($included as $name => $regex){
	      preg_match_all($regex, $match, $matches);
	      if(count($matches[0])) $all_matches = array_merge($all_matches, $matches);
	    }

      if(!count($all_matches)) return false;
    }
    return true;
  }
  
  public function lifetimes($lifetime_regexes, $match=false){
    if(!$match) $match = $_SERVER['REQUEST_URI'];
    foreach($lifetime_regexes as $regex=>$time){
      preg_match_all($regex, $match, $matches);
      if(count($matches[0])) return $time;
    }
    return false;
  }

  public function get(){
    $class = 'WaxCache'.$this->engine_type;
    if(!$this->engine) $this->engine = new $class($this->config['dir'], $this->lifetime, $this->suffix, false, $this->config);
    return $this->engine->get();
  }

  public function set($value){
    $class = 'WaxCache'.$this->engine_type;
    if(!$this->engine) $this->engine = new $class($this->config['dir'], $this->lifetime, $this->suffix, false, $this->config);
    return $this->engine->set($value);
  }

  public function expire(){
    $class = 'WaxCache'.$this->engine_type;
    if(!$this->engine) $this->engine = new $class($this->config['dir'], $this->lifetime, $this->suffix, false, $this->config);
    return $this->engine->expire();
  }

  public function valid($config, $format="html"){
    $class = 'WaxCache'.$this->engine_type;
    if(!$this->engine) $this->engine = new $class($this->config['dir'], $this->lifetime, $this->suffix, false, $this->config);
    
    if(!$this->excluded($config) && $this->included($config) && ($res = $this->engine->get()) ) return $res;
    else return false;
  }

  /**
   * this is whats ran outside of the framework
   * @param string $config
   * @return void
   */
  public function layout_cache_loader($config, $format="html"){
    Session::start();
    $this->identifier = $this->identifier();
    return $this->valid($config, $format);
  }

}

?>