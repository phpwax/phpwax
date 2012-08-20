<?
namespace Wax\Cache;

/**
 * Set of functions that each cache engine should have
 *
 */
interface Engine{
  
  public function get();
  public function set($value);
  public function expire();  
  
}
?>