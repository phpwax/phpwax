<?
interface Cacheable{
  public function cache_identifier();
  public function cacheable();
  public function cached();
  public function cache_set($value);
  public function cache_expire();
}

?>