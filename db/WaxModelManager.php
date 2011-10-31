<?php
/**
*
*/
class WaxModelManager {
  
  public static $storage = array();
  

  public function set($object) {
    $key = spl_object_hash($object);
    self::$storage[$key] = $object;
    return $key;
  }
  
  public function get($key) {
    return self::$storage[$key];
  }

  public function contains($key) {
    return array_key_exists($key, self::$storage);
  }
  
  public function detach($key) {
    unset(self::$storage[$key]);
  }
   
  public function count() {
    return count(self::$storage);
  }
    
  
  
}

