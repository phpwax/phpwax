<?
/**
 * cache interface system
 * in general $model should be an instance of WaxCacheLoader
 * To use cache you will have to set up you config.yaml file for each type of cache
 *
 * Recommend Usage:
 *
 * Querying & Returning :
 * if($this->use_cache && $this->cache_enabled($type)){
 *  if($this->cached(waxcacheloader, $type) ) return $this->cached(waxcacheloader, $type);
 * }
 * 
 * Setting (on destruct):
 *
 * if($cache_content && waxcacheloader) $this->cache_set(waxcacheloader, $cache_content);
 *
 *
 */
interface Cacheable{
  /**
   * return a unique string for this cacheable item 
   * - in the cache of file cache its the full file path
   * @param string $model 
   * @return string
   */
  public function cache_identifier($cache_loader);
  /**
   * returns whether or not the model passed in is 
   * allowed to be read from the cache
   * @param string $model 
   * @param string $type 
   * @return boolean
   */
  public function cacheable($model,$type);
  /**
   * calls cacheable to make sure can be used then 
   * returns the cached model or false
   * @param string $model 
   * @param string $type 
   * @return mixed
   */
  public function cached($model,$type);
  /**
   * using the model passed, set the cache
   * to the value passed in
   * @param string $model 
   * @param string $value 
   * @return void
   */
  public function cache_set($model, $value);
  /**
   * see if cache is turned on for the type passed in
   *
   * @param string $type 
   * @return void
   */
  public function cache_enabled($type);
}

?>