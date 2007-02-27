<?php
/**
 * Yahoo User Interface Helper class
 * Note, this doesn't link to any physical files, rather the library is served
 * directly from Yahoo's API.
 * @package PHP-WAX
 * @author Ross Riley
 **/
class YUIHelper extends JavascriptHelper{

  public function yui_default_js() {
    $sources = array("http://yui.yahooapis.com/2.2.0/build/yahoo/yahoo-min.js",
      "http://yui.yahooapis.com/2.2.0/build/event/event-min.js",
      "http://yui.yahooapis.com/2.2.0/build/animation/animation-min.js",
      "http://yui.yahooapis.com/2.2.0/build/dom/dom-min.js",
      "http://yui.yahooapis.com/2.2.0/build/animation/animation-min.js",
      "http://yui.yahooapis.com/2.2.0/build/dragdrop/dragdrop-min.js",
      "http://yui.yahooapis.com/2.2.0/build/connection/connection-min.js");
    return $this->yui_jsinclude($sources);
  }
  
  public function yui_default_css() {
    $sources = array("http://yui.yahooapis.com/2.2.0/build/reset-fonts-grids/reset-fonts-grids.css");
    return $this->yui_csslink($sources);
  }
  
  public function yui_container_files() {
    $css = array("http://yui.yahooapis.com/2.2.0/build/container/assets/container.css");
    $js = array("http://yui.yahooapis.com/2.2.0/build/container/container-min.js");
    return $this->yui_csslink($css).$this->yui_jsinclude($js);
  }
  
  
  
  protected function yui_csslink($sources, $options=array()) {
    foreach($sources as $source) {
        $contents[] = $this->tag("link",
           array_merge(array("rel" => "Stylesheet",
                             "type" => "text/css",
                             "media" => "screen",
                             "href" => $source), $options));
    }
    return implode("", $contents);
  }
  
  protected function yui_jsinclude($sources, $options=array()) {
    foreach($sources as $source) {
        $contents[] = $this->content_tag("script", "",
             array_merge(array("type" => "text/javascript",
                               "src" => $source), $options));
    }
    return implode("", $contents);
  }




} 


?>