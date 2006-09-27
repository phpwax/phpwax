<?php
/**
  * Cache Class 
  *
  * @package wx.php.core
  */
  
class WXCache extends ApplicationBase {
	
  
	var $cache_duration = 24;	
	
	
  function write_to_cache($contents)
  {
    $filename = CACHE_DIR .$this->controller . "_" . $this->action;
    $result = File::write_to_file($filename, $contents);
    
  }
  
  static function read_from_cache()
  {
    $filename = $this->controller . "_" . $this->action;
    return File::read_from_file($filename);
    
  }
  
  static function clear_from_cache()
  {    
    
  }
  
  static function clear_all_cache()
  {
    
  }	
	
	
}

?>
