<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/

/**
 *  Simple Helpers to create links to images/js/css
 */
class AssetTagHelper extends WXHelpers {

    /**
     *  @var string[]
     */
  public $javascript_default_sources = null;
  static public $asset_server = false;


  public function __construct() {
    $this->javascript_default_sources =	array('prototype', 'builder','effects', 'dragdrop', 'controls', 'slider');
    self::$asset_server = Config::get("assets");
  }
  
  public function serve_asset($type, $namespace, $filename) {
    if($server = self::$asset_server) $source .= "http://".$server;
    $source .= "/$type/$namespace/$filename";
    return $source;
  }
  
  public function javascript_asset($namespace, $filename, $options=array()) {
    if(!strpos($filename, ".js")) $filename .=".js";
    return $this->javascript_include_tag($this->serve_asset("javascripts", $namespace, $filename), $options);
  }
  
  public function stylesheet_asset($namespace, $filename, $options=array()) {
    if(!strpos($filename, ".css")) $filename .=".css";
    return $this->stylesheet_link_tag($this->serve_asset("stylesheets", $namespace, $filename), $options);
  }
  
  public function image_asset($namespace, $filename, $options=array()) {
    return $this->image_tag($this->serve_asset("images", $namespace, $filename), $options);
  }

  public function javascript_include_tag() {
    if(func_num_args() > 0) {
      $sources = func_get_args();     
      $options = (is_array(end($sources)) ? array_pop($sources) : array());          
      if(in_array('defaults', $sources)) {
        if(is_array($this->javascript_default_sources)) {
          $sources = array_merge($this->javascript_default_sources, $sources);    
        }                  
        if(file_exists(SCRIPT_DIR. "application.js")) {
          $sources[] = 'application';
        }
          # remove defaults from array
        unset($sources[array_search('defaults', $sources)]);  
      }
      $contents = array();
      foreach($sources as $source) {
        $source = $this->javascript_path($source);
        $contents[] = $this->content_tag("script", "",
        array_merge(array("type" => "text/javascript", "src" => $source), $options));
      }
      return implode("", $contents);
    }
  }
  
  public function stylesheet_link_tag() {
    if(func_num_args() > 0) {
      $sources = func_get_args();     
      $options = (is_array(end($sources)) ? array_pop($sources) : array());
      $contents = array();
      foreach($sources as $source) {
        $source = $this->stylesheet_path($source);
        $contents[] = $this->tag("link",
              array_merge(array("rel" =>    "Stylesheet",
                                "type" =>   "text/css",
                                "media" =>  "screen",
                                "href" =>   $source), $options));
      }
      return implode("", $contents);
    }
  }

  public function image_tag($source, $options = array()) {
    $options['src'] = $this->image_path($source);
    $options['alt'] = array_key_exists('alt',$options) ? $options['alt'] : ucfirst(reset($file_array = explode('.', basename($options['src']))));
    if(isset($options['size'])) {
      $size = explode('x', $options["size"]);         
      $options['width'] = reset($size);
      $options['height'] = end($size);
      unset($options['size']);
    }
    return $this->tag("img", $options);
  }
  
  public function js_bundle($name, $options = array(), $plugin="") {
    if(ENV=="development" || defined("NO_JS_BUNDLE")) {
      if($plugin){
        $base = PLUGIN_DIR.$plugin."/resources/public/";
        $d = $base."javascripts/";
      }else{
        $base = PUBLIC_DIR;
        $d = $base."javascripts/".$name;       
      } 
      if(!is_readable($d)) return false;
      foreach($this->iterate_dir($d, "js") as $file){
        $name = $file->getPathName();
        $ret .= $this->javascript_include_tag("/".str_replace($base, "", $name), $options);
      }
    } else $ret = $this->javascript_include_tag("/javascripts/build/{$name}_combined.js", $options);
    return $ret;
  }
  
  public function css_bundle($name, $options=array(), $plugin="") {
    if(ENV=="development" || defined("NO_CSS_BUNDLE")) {     
      if($plugin){
        $base = PLUGIN_DIR.$plugin."/resources/public/";
        $d = $base."stylesheets/";
      }else{
        $base = PUBLIC_DIR;
        $d = $base."stylesheets/".$name;       
      }      
      if(!is_readable($d)) return false;       
      foreach($this->iterate_dir($d, "css") as $file){
        $name = $file->getPathName();
        $ret .= $this->stylesheet_link_tag("/".str_replace($base, "", $name), $options);
      }      
    } else $ret = $this->stylesheet_link_tag("build/{$name}_combined.css", $options);
    return $ret;
  }
  public function iterate_dir($d, $ext){
    $files = array();
    $dir = new RecursiveIteratorIterator(new RecursiveRegexIterator(new RecursiveDirectoryIterator($d, RecursiveDirectoryIterator::FOLLOW_SYMLINKS), '#.*#i'), true);
    foreach($dir as $file) if(!$file->isDir() && $file->getExtension() == $ext) $files[] = $file;
    return $files;
  }

	public function git_revision(){
	  $rev = "";
	  if(ENV =="development") return $rev;
		
		if(!$rev = Config::get('GIT_HEAD')){
			if(!$branch = Config::get('repo_branch')) $branch = "master";
			$path = WAX_ROOT.".git/refs/heads/".$branch;
			if(is_readable($path) && is_file($path)) $rev = "?r=".substr(file_get_contents($path),0,8);
			Config::set('GIT_HEAD', $rev);
		}
		return $rev;
	}
  
  protected function image_path($source) {
    return $this->compute_public_path($source, 'images', 'png');
  }
  
  protected function stylesheet_path($source) {
    return $this->compute_public_path($source, 'stylesheets', 'css');
  }
  
  protected function javascript_path($source) {
    return $this->compute_public_path($source, 'javascripts', 'js');
  }
    
  private function compute_public_path($source, $dir, $ext) {
    //  Test whether source is a URL, ie. starts something://
    if(!preg_match('/^[-a-z]+:\/\//', $source)) {
      //  Source is not a URL. If path doesn't start with '/', prefix /$dir/
      if($source{0} != '/') {
        $source = "/{$dir}/{$source}";
      }
			if($ext && !strpos($source, ".")) {
				$source = $source.".".$ext;
			}
    }
    return $source.$this->git_revision();
  }
    
}

?>
