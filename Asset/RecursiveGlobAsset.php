<?php
use Assetic\Asset\GlobAsset;

/**
* A collection of assets loaded by glob.
*
* @author Ross Riley
*/
class RecursiveGlobAsset extends GlobAsset {

  /**
  * Constructor.
  *
  * @param string|array $globs A single glob path or array of paths
  * @param string $glob file pattern
  * @param array $filters An array of filters
  * @param string $root The root directory
  * @param array $vars
  */
  public function __construct($globs, $pattern, $filters = array(), $root = null, array $vars = array()) {
    $this->globs = (array) $globs;
    $this->pattern = $pattern;
    $this->initialized = false;
    parent::__construct(array(), $filters, $root, $vars);
  }

  public function iterate_dir($d, $ext){
    $files = array();
    $dir = new RecursiveIteratorIterator(
        new RecursiveRegexIterator(
          new RecursiveDirectoryIterator($d, RecursiveDirectoryIterator::FOLLOW_SYMLINKS), 
          '#^.+\.'.$this->pattern.'$#i'
        ), 
      true);
    foreach($dir as $file) $files[] = $file;
    return $files;
  }
  
  
  private function initialize() {
    foreach ($this->globs as $glob) {
      $files = $this->iterate_dir(dirname($glob));
      print_r($files); exit;
      $this->initialized = true;
    }
  }
  
  
}