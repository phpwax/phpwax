<?php
/**
  * Cache Class 
  *
  * @package wx.php.core
  */
  
class WXCache extends ApplicationBase 
{	
	/**
	  *  writes the information passed into the cache file
	  *  as long as the file is within date
	  *  @returns boolean
	  */ 
  static function write_to_cache($contents, $filename, $cachelength=1000) 
  {
    //check the cache is in date
    $expired = WXCache::cache_expired($filename, $cachelength); 
 		$filename = CACHE_DIR.$filename;
    //the cache is still in date and a readable file
		if(!$expired && is_readable($filename) ) 
		{
  		return true;
		}		
    //if the cache file doesnt exist of is out of date, write the data
    if(!$result = File::write_to_file($filename, $contents)) 
    {
      //throw error message if the write fails
			throw new WXPermissionsException("Please make sure the tmp directory is writable", "Cache Write Error");
		}
		//return that cache has been written successfully 
		else 
		{
		  return true;
		}
  }
  
  /**
	  *  reads a page from the cache, checks its in date	  
	  *  @returns boolean or file contents
	  */ 
  static function read_from_cache($filename, $cachelength=1000) 
  {
    if(!WXCache::cache_expired($filename, $cachelength) )
    { 
     $filename = CACHE_DIR.$filename; 
     return File::read_from_file($filename);
    }
    else
    {
      return false;  
    }
  }
  
  /**
	  *  used to check that the cache file is in date
	  *  and exists
	  *  @returns boolean
	  */ 
  static function cache_expired($filename, $cachelength=1000)
  {
    
   $filename = CACHE_DIR.$filename; 
   if(!is_readable($filename))
   {
    return true;  
   }  
   elseif(File::is_older_than($filename, $cachelength) )
   {
    return true;     
   } 
   else
   {
    return false;  
   }
   
  }
  
  /**
    * deletes a specified file from the cache
    * returns true on success
    */  
  static function clear_from_cache($filename) 
  {     
    $filename = CACHE_DIR.$filename;
    if(is_readable($filename))
    {
      @unlink($filename);  
      return true;
    }
    else
    {
      return false;    
    }
  }
  
  /**
    * deletes all files from the cache 
    * directory
    */  
  static function clear_cache() 
  {
     $files = glob(CACHE_DIR . "*");
     foreach($files as $k => $filename)
     {
      @unlink($filename); 
     } 
  }
	
	
}

?>
