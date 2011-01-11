<?
/**
 * Set of functions that each cache engine should have
 *
 */
interface CacheEngine{
  
  public function get();
  public function set($value);
  public function expire();  
  
}
?>