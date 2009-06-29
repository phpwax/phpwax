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
	public $engine_type = false;
	public $suffix = 'cache';
	public $dir = false;
  public $identifier = false;
  
  public function __construct($engine="File",$dir="", $lifetime=3600){
    $this->engine_type = $engine;
    $this->dir = $dir;  
    $this->lifetime = $lifetime;  
    if(!is_readable($this->dir)){
      mkdir($this->dir);
      chmod($this->dir, 0777);
    }    
  }

	public function identifier($prefix, $data){
	  if($this->identifier) return $this->identifier;
	  else{
		  $str .= $this->dir.$prefix;
      if(count($data)) $str .= "-data-".serialize($data);
      if(count($_GET)) $str .= "-get-".serialize($_GET);
      if(count($_POST)) $str .= "-post-".serialize($_POST);      
      $this->identifier = $str.'.'.$this->suffix;
      return $this->identifier;
    }
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
    $engine = new $class($this->identifier, $this->lifetime);
    return $engine->get();
  }
  
  public function set($value){
    $class = 'WaxCache'.$this->engine_type;
    $engine = new $class($this->identifier, $this->lifetime);
    return $engine->set($value);
  }
  
  public function expire(){
    $class = 'WaxCache'.$this->engine_type;
    $engine = new $class($this->identifier, $this->lifetime);
    return $engine->expire();
  }
  
}

?>