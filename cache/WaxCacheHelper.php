<?php

/**
 * Cache Helper....
 * Allows capturing of output to cache
 *
 * @package default
 * @author Ross Riley
 */

class WaxCacheHelper extends WXHelpers {
  
  public $cache = false;
  

  public function cache_start($label) {
    global $cache;
    $cache = new WaxCache($label);
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
  
  public function cache_valid($label, $return = false) {
    $cache = new WaxCache($label);
    return $cache->valid($return);
  }

  public function cache_get($label) {
    $cache = new WaxCache($label);
    if($cache->valid()) return $cache->get();
  }
  
  public function cache_reset($label) {
    $cache = new WaxCache($label);
    if($data = $cache->valid(true))
      return $cache->set($data);
  }

}