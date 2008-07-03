<?php

/**
 * Cache Helper....
 * Allows capturing of output to cache
 *
 * @package default
 * @author Ross Riley
 */

class CacheHelper extends WXHelpers {
  
  public $cache = false;
  

  public function cache_start($label) {
    global $cache;
    $cache = new Cache($label);
    if(!$cache->enabled) return true;
    ob_start();
    if($cache->valid()) {
      $cache->reading=true;
      return false;
    }
    return true;
  }
  
  
  public function cache_end() {
    global $cache;
    if(!$cache->enabled) return true;
    if($cache->reading) {
      $cache->reading = false;
      ob_end_clean();
      echo $cache->get();
      return true;
    }
    $content = ob_get_contents();
    $cache->set($content);
    ob_end_flush();
  }
  
  public function cache_valid($label) {
    $cache = new Cache($label);
    return $cache->valid();
  }

}