<?php
/*
 * @package PHP-Wax
 *
 * This class is based in part on the helpers functionality in the PHP on Trax Framework. 
 * For more information, see:
 *  http://phpontrax.com/
 */
 
class JavascriptHelper extends WXHelpers {
  
  function __construct() {
    $this->javascript_path = '/javascripts';
  }
  
  protected function options_for_javascript($options) {
    $javascript = array();
    if(is_array($options)) {
      $javascript = array_map(create_function('$k, $v', 'return "{$k}:{$v}";'), array_keys($options), array_values($options));
      sort($javascript);
    }
    return '{' . implode(', ', $javascript) . '}';
  }
  
  private function array_or_string_for_javascript($option) {
    if(is_array($option)) {
        $js_option = "['" . implode('\',\'', $option) . "']";
    } elseif (!is_null($option)) {
        $js_option = "'{$option}'";
    }
    return $js_option;
  }
  
     
  # Returns a link that'll trigger a javascript $function using the 
  # onclick handler and return false after the fact.
  #
  # Examples:
  #   link_to_function("Greeting", "alert('Hello world!')")
  #   link_to_function(image_tag("delete"), "if confirm('Really?'){ do_delete(); }")
  public function link_to_function($name, $function, $html_options = array()) {
      return $this->content_tag("a", $name, array_merge(array('href' => "#", 'onclick' => "{$function}; return false;"), $html_options));
  }
    
  # Escape carrier returns and single and double quotes for JavaScript segments.
  public function escape_javascript($javascript) {
      return preg_replace('/\r\n|\n|\r/', "\\n",
             preg_replace_callback('/["\']/', create_function('$m', 'return "\\{$m}";'),
             (!is_null($javascript) ? $javascript : '')));
  }
  
  # Returns a JavaScript tag with the $content inside. Example:
  #   javascript_tag("alert('All is good')") => <script type="text/javascript">alert('All is good')</script>
  public function javascript_tag($content) {
      return $this->content_tag("script", $this->javascript_cdata_section($content), array('type' => "text/javascript"));
  }
  
  public function javascript_cdata_section($content) {
      return "\n//" . $this->cdata_section("\n{$content}\n//") . "\n";
  }
  
  
}

?>