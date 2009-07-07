<?php
/**
	* @package PHP-Wax
  */

/**
 *	Class for loading in pre existing cache files based on the current location or value passed in
 *  @package PHP-Wax
 */
class WaxCacheLoader {
		
	public $lifetime = 3600;
	public $config = array();
	public $engine_type = 'File';
	public $suffix = 'cache';
	public $dir = false;
  public $identifier = false;
  
  
  public function __construct($engine="File",$dir="", $lifetime=3600, $format='html'){
    if($engine) $this->engine_type = $engine;
    if($dir) $this->dir = $dir;
    else $this->dir = CACHE_DIR;
    
    $this->lifetime = $lifetime;  
    if(!is_readable($this->dir)){
      mkdir($this->dir);
      chmod($this->dir, 0777);
    }    
    $this->suffix = $format.'.'.$this->suffix;
  }

	public function identifier(){
	  if(!$this->identifier){
		  $class = 'WaxCache'.$this->engine_type;
      $engine = new $class($this->dir, $this->lifetime, $this->suffix);
      $this->identifier =  $engine->make_identifier();      
    }
    return $this->identifier;
	}

  public function excluded($config){
    if($config['exclude_post'] == "yes" && count($_POST)) return true;
    if(isset($config['exclusions'])){
      $excluded = $config['exclusions'];
      $all_matches = array();
	    if(is_array($excluded)){
	      foreach($excluded as $name => $regex){
	        preg_match_all($regex, $_SERVER['REQUEST_URI'], $matches);	      
	        if(count($matches[0])) $all_matches = array_merge($all_matches, $matches);
	      }	    
	    }else preg_match_all($excluded, $_SERVER['REQUEST_URI'], $all_matches);	    	    
	    if(count($all_matches)) return true;
    }
    return false;
  }
  
  public function get(){
    $class = 'WaxCache'.$this->engine_type;
    $engine = new $class($this->dir, $this->lifetime,$this->suffix, $this->identifier);
    return $engine->get();
  }
  
  public function set($value){
    $class = 'WaxCache'.$this->engine_type;
    $engine = new $class($this->dir, $this->lifetime, $this->suffix, $this->identifier);
    return $engine->set($value);
  }
  
  public function expire(){
    $class = 'WaxCache'.$this->engine_type;
    $engine = new $class($this->dir, $this->lifetime, $this->suffix,$this->identifier);
    return $engine->expire();
  }
  
  
  
  public function layout_cache_loader($config){
    $this->identifier = $this->identifier();
    $class = 'WaxCache'.$this->engine_type;
    $engine = new $class($this->dir, $this->lifetime, $this->suffix, $this->identifier);
    $engine->marker = "<!-- FROM CACHE - NO WAX -->";    
    
    if(!$this->excluded && $engine->get()) return $engine->get();
    else return false;
  }
  
}

?>