<?php
/*
 * @package wxframework.core
 *
 * This class is based in part on the helpers functionality in the PHP on Trax Framework. 
 * For more information, see:
 *  http://phpontrax.com/
 */

/**
 *  Utility to help build HTML/XML link tags for public assets
 */
class AssetTagHelper extends WXHelpers {

    /**
     *  @var string[]
     */
    var $javascript_default_sources = null;

    /**
     *  @todo Document this method
     *
     *  @uses javascript_default_sources
     */
    function __construct() {
        $this->javascript_default_sources =	array('prototype', 'scriptaculous');    
    }
    
    /**
     *  Compute public path to an asset
     *
     *  Build the public path, suitable for inclusion in a URL, to an
     *  asset.  Arguments are the filename, directory and extension of
     *  the asset.
     *  @param string  Filename of asset
     *  @param string  Default directory name, if none in $source
     *  @param string  Default file extension, if none in $source
     *  @return string Public path to the asset
     *  @uses controller_object
     *  @uses ActionController::asset_host
     */
    private function compute_public_path($source, $dir, $ext) {
 
        //  Test whether source is a URL, ie. starts something://
        if(!preg_match('/^[-a-z]+:\/\//', $source)) {

            //  Source is not a URL.
            //  If path doesn't start with '/', prefix /$dir/
            if($source{0} != '/') {
                $source = "/{$dir}/{$source}";
            }
						
						if($ext && !strpos($source, ".")) {
							$source = $source.".".$ext;
						}

               
        }
        return $source;
    }
    
    /**
     *  Compute public path to a javascript asset
     *
     *  Build the public path, suitable for inclusion in a URL, to a
     *  javascript asset.  Argument is the filename of the asset.
     *  Default directory to 'javascripts', extension to '.js'
     *  @param string  Filename of asset, in one of the formats
     *                 accepted as the $filename argument of
     *                 {@link compute_public_path()}
     *  @return string Public path to the javascript asset
     *  @uses compute_public_path()
     */
    protected function javascript_path($source) {
        return $this->compute_public_path($source, 'javascripts', 'js');
    }
    
    /**
     *  Return script include tag for one or more javascript assets
     *
     *  javascript_include_tag("xmlhr"); =>
     *   <script type="text/javascript" src="/javascripts/xmlhr.js"></script>
     *
     *  javascript_include_tag("common.javascript", "/elsewhere/cools"); =>
     *   <script type="text/javascript" src="/javascripts/common.javascript"></script>
     *   <script type="text/javascript" src="/elsewhere/cools.js"></script>
     *
     *  javascript_include_tag("defaults"); =>
     *   <script type="text/javascript" src="/javascripts/prototype.js"></script>
     *   <script type="text/javascript" src="/javascripts/effects.js"></script>
     *   <script type="text/javascript" src="/javascripts/controls.js"></script>
     *   <script type="text/javascript" src="/javascripts/dragdrop.js"></script>   
     *  @param mixed  The arguments are zero or more strings, followed
     *                by an optional array containing options
     *  @return string
     *  @uses content_tag()
     *  @uses javascript_default_sources
     *  @uses javascript_path()
     */
    public function javascript_include_tag() {
        if(func_num_args() > 0) {
            $sources = func_get_args();     
            $options = (is_array(end($sources))
                        ? array_pop($sources) : array());          
            if(in_array('defaults', $sources)) {
                if(is_array($this->javascript_default_sources)) {
                    $sources = array_merge($this->javascript_default_sources,
                                           $sources);    
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
                     array_merge(array("type" => "text/javascript",
                                       "src" => $source), $options));
            }
            return implode("", $contents);
        }
    }
    
    /**
     *  Compute public path to a stylesheet asset
     *
     *  Build the public path, suitable for inclusion in a URL, to a
     *  stylesheet asset.  Argument is the filename of the asset.
     *  Default directory to 'stylesheets', extension to '.css'
     *  @param string  Filename of asset, in one of the formats
     *                 accepted as the $filename argument of
     *                 {@link compute_public_path()}
     *  @return string Public path to the stylesheet asset
     *  @uses compute_public_path()
     */
    protected function stylesheet_path($source) {
        return $this->compute_public_path($source, 'stylesheets', 'css');
    }
    
    /**
     *  Build link tags to one or more stylesheet assets
     *
     *  @param mixed  One or more assets, optionally followed by an
     *                array describing options to apply to the tags
     *                generated for these assets.<br>  Each asset is a
     *                string in one of the formats accepted as value
     *                of the $source argument of
     *                {@link stylesheet_path()}.<br>  The optional last
     *                argument is an array whose keys are names of
     *                attributes of the link tag and whose corresponding
     *                values are the values assigned to each
     *                attribute.  If omitted, options default to:
     *                <ul>
     *                  <li>"rel" => "Stylesheet"</li>
     *                  <li>"type" => "text/css"</li>
     *                  <li>"media" => "screen"</li>
     *                  <li>"href" => <i>path-to-source</i></li>
     *                </ul>
     *  @return string  A link tag for each asset in the argument list
     *  @uses stylesheet_path()
     *  @uses tag()
     */
    public function stylesheet_link_tag() {
        if(func_num_args() > 0) {
            $sources = func_get_args();     
            $options = (is_array(end($sources))
                        ? array_pop($sources) : array());
            $contents = array();
            foreach($sources as $source) {
                $source = $this->stylesheet_path($source);
                $contents[] = $this->tag("link",
                   array_merge(array("rel" => "Stylesheet",
                                     "type" => "text/css",
                                     "media" => "screen",
                                     "href" => $source), $options));
            }
            return implode("", $contents);
        }
    }
    
    /**
     *  Compute public path to a image asset
     *
     *  Build the public path, suitable for inclusion in a URL, to a
     *  image asset.  Argument is the filename of the asset.
     *  Default directory to 'images', extension to '.png'
     *  @param string  Filename of asset, in one of the formats
     *                 accepted as the $filename argument of
     *                 {@link compute_public_path()}
     *  @return string Public path to the image asset
     *  @uses compute_public_path()
     */
    protected function image_path($source) {
        return $this->compute_public_path($source, 'images', 'png');
    }
    
    /**
     *  Build image tags to an image asset
     *
     *  @param mixed  An image asset optionally followed by an
     *                array describing options to apply to the tag
     *                generated for this asset.<br>The asset is a
     *                string in one of the formats accepted as value
     *                of the $source argument of
     *                {@link image_path()}.<br>  The optional second
     *                argument is an array whose keys are names of
     *                attributes of the image tag and whose corresponding
     *                values are the values assigned to each
     *                attribute.  The image size can be specified in
     *                two ways: by specifying option values "width" =>
     *                <i>width</i> and "height" => <i>height</i>, or
     *                by specifying option "size" => "<i>width</i>
     *                x <i>height</i>".  If omitted, options default to:
     *                <ul>
     *                  <li>"alt" => <i>humanized filename</i></li>
     *                  <li>"width" and "height" value computed from
     *                       value of "size"</li>
     *                </ul>
     *  @return string  A image tag for each asset in the argument list
     *  @uses image_path()
     *  @uses tag()
     */
  public function image_tag($source, $options = array()) {
    $options['src'] = $this->image_path($source);
    $options['alt'] = array_key_exists('alt',$options)
        ? $options['alt']
        : ucfirst(reset($file_array =
                         explode('.', basename($options['src']))));
    if(isset($options['size'])) {
        $size = explode('x', $options["size"]);         
        $options['width'] = reset($size);
        $options['height'] = end($size);
        unset($options['size']);
    }
    return $this->tag("img", $options);
  }
    
}

?>
