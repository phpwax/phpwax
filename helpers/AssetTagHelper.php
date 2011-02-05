<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/

/**
 *  Simple Helpers to create links to images/js/css
 */
class AssetTagHelper extends WaxHelper {

    /**
     *  @var string[]
     */
  public $javascript_default_sources = null;
  static public $asset_server = false;


  public function __construct() {
    $this->javascript_default_sources =	array('prototype', 'builder','effects', 'dragdrop', 'controls', 'slider');
    self::$asset_server = Config::get("assets");
  }

  protected function javascript_include_tag() {
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
  
  protected function stylesheet_link_tag() {
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


  
  public function js_bundle($name, $options = array()) {
    if(ENV=="development" || defined("NO_JS_BUNDLE")) {
      foreach(glob(PUBLIC_DIR."javascripts/$name/*.js") as $file){
        $ret .= $this->javascript_include_tag("/javascripts/$name/".basename($file), $options);
      }
    } else $ret = $this->javascript_include_tag("/javascripts/build/{$name}_combined", $options);
    return $ret;
  }
  
  public function css_bundle($name, $options=array()) {
    if(ENV=="development" || defined("NO_CSS_BUNDLE")) {
      foreach(glob(PUBLIC_DIR."stylesheets/$name/*.css") as $file){
        $ret .= $this->stylesheet_link_tag("/stylesheets/$name/".basename($file), $options);
      }
    } else $ret = $this->stylesheet_link_tag("build/{$name}_combined", $options);
    return $ret;
  }

	protected function git_revision(){
		$rev = "";
		if(!$rev = Config::get('GIT_HEAD')){
			if(is_readable(WAX_ROOT.".git/HEAD")) $rev_ref = trim(substr(file_get_contents(WAX_ROOT.".git/HEAD"),5));
			if(is_readable(WAX_ROOT.".git/info/refs")) {
				$gresource = fopen(WAX_ROOT.".git/info/refs","r");
				while (($buffer = fgets($gresource, 4096)) !== false) {
					if(strpos($buffer,$rev_ref)) $rev = substr($buffer,0,6);
				}
			}
			elseif(is_readable(WAX_ROOT.".git/".$rev_ref)) $rev = substr(file_get_contents(WAX_ROOT.".git/".$rev_ref),0,6);
			$rev = "?r=$rev";
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
