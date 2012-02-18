<?php
namespace Wax\Template\Helper;
use Wax\Template\CodeGenerator;
use Wax\Config\Config;

/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/

/**
 *  Simple Helpers to create links to images/js/css
 */
class AssetTagHelper extends Helper {

    /**
     *  @var string[]
     */
  public $javascript_default_sources = null;
  static public $asset_server = false;

  protected static function javascript_include_tag() {
    if(func_num_args() > 0) {
      $sources = func_get_args();     
      $options = (is_array(end($sources)) ? array_pop($sources) : array());          
      if(in_array('defaults', $sources)) {
        if(is_array(self::$javascript_default_sources)) {
          $sources = array_merge(self::$javascript_default_sources, $sources);    
        }                  
        if(file_exists(SCRIPT_DIR. "application.js")) {
          $sources[] = 'application';
        }
          # remove defaults from array
        unset($sources[array_search('defaults', $sources)]);  
      }
      $contents = array();
      foreach($sources as $source) {
        $source = self::javascript_path($source);
        $contents[] = self::content_tag("script", "",
        array_merge(array("type" => "text/javascript", "src" => $source), $options));
      }
      return implode("", $contents);
    }
  }
  
  protected static function stylesheet_link_tag() {
    if(func_num_args() > 0) {
      $sources = func_get_args();     
      $options = (is_array(end($sources)) ? array_pop($sources) : array());
      $contents = array();
      foreach($sources as $source) {
        $source = self::stylesheet_path($source);
        $contents[] = self::tag("link",
              array_merge(array("rel" =>    "Stylesheet",
                                "type" =>   "text/css",
                                "media" =>  "screen",
                                "href" =>   $source), $options));
      }
      return implode("", $contents);
    }
  }


  
  public static function js_bundle($name, $options = array(), $plugin="") {
    if(ENV=="development" || defined("NO_JS_BUNDLE")) {
      if($plugin) $base = PLUGIN_DIR.$plugin."/resources/public/";
      else $base = PUBLIC_DIR;
      $d = $base."javascripts/".$name;
      $dir = new \RecursiveIteratorIterator(new \RecursiveRegexIterator(new \RecursiveDirectoryIterator($d, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS), '#(?<!/)\.js$|^[^\.]*$#i'), true);
      foreach($dir as $file){
        $name = $file->getPathName();
        if(is_file($name))$ret .= self::javascript_include_tag("/".str_replace($base, "", $name), $options);
      }
    } else $ret = self::javascript_include_tag("/javascripts/build/{$name}_combined", $options);
    return $ret;
  }
  
  public static function css_bundle($name, $options=array(), $plugin="") {
    if(ENV=="development") {     
      if($plugin) $base = PLUGIN_DIR.$plugin."/resources/public/";
      else $base = PUBLIC_DIR;
      $d = $base."stylesheets/".$name;       
      $dir = new \RecursiveIteratorIterator(new \RecursiveRegexIterator(new \RecursiveDirectoryIterator($d, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS), '#(?<!/)\.css$|^[^\.]*$#i'), true);
      foreach($dir as $file){
        $name = $file->getPathName();
        if(is_file($name)) $ret .= self::stylesheet_link_tag("/".str_replace($base, "", $name), $options);
      }
    } else $ret = self::stylesheet_link_tag("build/{$name}_combined", $options);
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
	
  
  protected static function image_path($source) {
    return self::compute_public_path($source, 'images', 'png');
  }
  
  protected static function stylesheet_path($source) {
    return self::compute_public_path($source, 'stylesheets', 'css');
  }
  
  protected static function javascript_path($source) {
    return self::compute_public_path($source, 'javascripts', 'js');
  }
    
  protected static function compute_public_path($source, $dir, $ext) {
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
    return $source.self::git_revision();
  }
    
}


