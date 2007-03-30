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
    $js = array("http://static.webxpress.com/yui/javascripts/container-min.js");
    $css = array("http://yui.yahooapis.com/2.2.0/build/container/assets/container.css");
    return $this->yui_jsinclude($js).$this->yui_csslink($css);
  }
  
  public function yui_container($element, $options=array()) {
    $default_options = array("width"=>"'550px'", "visible"=>"false", "constraintoviewport"=>"true", "modal"=>"true",
      "underlay"=>"'shadow'", "fixedcenter"=>"true");
    $options = array_merge($default_options, $options);
    $javascript = 'YAHOO.namespace("cms.container");';
    $javascript .= 'function init_'.$element.'() {'."\n";
    $javascript .= 'YAHOO.cms.container.'.$element.' = new YAHOO.widget.Panel("'.$element.'",'.$this->options_for_javascript($options).'); ';     
    $javascript .= 'YAHOO.cms.container.'.$element.'.render(); }'."\n";
    $javascript .= 'YAHOO.util.Event.addListener(window, "load", init_'.$element.');';
    return $this->javascript_tag($javascript);
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