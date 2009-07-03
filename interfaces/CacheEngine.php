<?
/**
 * Set of functions that each cache engine should have
 *
 */
interface CacheEngine{
  
  public function get();
  public function set();
  public function valid();
  public function expire();  
  
}
?>