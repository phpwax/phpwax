<?php
/**
  * Cache Class 
  *
  * @package wx.php.core
  */
  
class WXCache extends ApplicationBase 
{	
	
  static function write_to_cache($contents, $filename, $cachelength) {
    $filename = CACHE_DIR.$filename;
		if(is_readable($filename) && !File::is_older_than($filename, $cachelength)) {
			return true;
		}
    if(!$result = File::write_to_file($filename, $contents)) {
			throw new WXPermissionsException("Please make sure the tmp directory is writable", "Cache Write Error");
		} else {
		return true;
		}
  }
  
  static function read_from_cache($filename) {
    return File::read_from_file($filename);
  }
  
  static function clear_from_cache() {    
    
  }
  
  static function clear_all_cache() {
    
  }
	
	
}

?>
