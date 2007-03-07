<?php
/*
 * @package wxframework.core
 *
 * This class is based in part on the helpers functionality in the PHP on Trax Framework. 
 * For more information, see:
 *  http://phpontrax.com/
 */

/**
 *  @todo Document this class
 */
class UrlHelper extends WXHelpers {
	

    /**
     * Creates a link tag of the given +name+ using an URL created by
     * the set of +options+. 
     * It's also possible to pass a string instead of an options hash
     * to get a link tag that just points without consideration. If
     * null is passed as a name, the link itself will become the
     * name. 
     * The $html_options have a special feature for creating
     * javascript confirm alerts where if you pass ":confirm" => 'Are
     * you sure?', 
     * the link will be guarded with a JS popup asking that
     * question. If the user accepts, the link is processed, otherwise
     * not. 
     *
     * Example:
     *   link_to("Delete this page", array(":action" => "delete",
     * ":id" => $page->id), array(":confirm" => "Are you sure?")) 
     *  @return string
     *  @uses content_tag()
     *  @uses convert_confirm_option_to_javascript()
     *  @uses url_for()
     */
    public function link_to($name, $options = array(), $html_options = array()) {
        $html_options =
            $this->convert_confirm_option_to_javascript($html_options);
        if(is_string($options)) {
            $href = array("href" => $options);
            if(count($html_options) > 0) {
                $html_options = array_merge($html_options, $href);
            } else {
                $html_options = $href;
            }
            if(!$name) {
                $name = $options;
            }
            $html = $this->content_tag("a", $name, $html_options);
        } else {
            $url = $this->url_for($options);
            if(!$name) {
                $name = $url;
            }
            $href = array("href" => $url);
            if(count($html_options) > 0) {
                $html_options = array_merge($html_options, $href);
            } else {
                $html_options = $href;
            }
            $html = $this->content_tag("a", $name, $html_options);
        }
        return $html;
    }

    /**
     *  @todo Document this method
     *  @param string[] Options
     *  @return string
     */
    protected function convert_confirm_option_to_javascript($html_options) {
        if(array_key_exists('confirm', $html_options)) {
            $html_options['onclick'] =
                "return confirm('".addslashes($html_options['confirm'])."');";
            unset($html_options['confirm']);
        }

				if(array_key_exists('window', $html_options)) {
					$html_options["onclick"] =
						"window.open('".$html_options['window']."', 'window_name', 'window_options'); return false";
				}
        return $html_options;
    }

    /**
     *  @todo Document this method
     *  @param mixed[]
     *  @param mixed[]
     *  @return mixed[]
     */
    protected function convert_boolean_attributes(&$html_options, $bool_attrs) {
        foreach($bool_attrs as $x) {
            if(@array_key_exists($x, $html_options)) {
                $html_options[$x] = $x;
            }
        }
        return $html_options;
    }

    /**
     *  @todo Document this method
     *  @param string
     *  @param mixed[]
     *  @param mixed[]
     *  @return string
     *  @uses convert_boolean_attributes()
     *  @uses convert_confirm_option_to_javascript()
     *  @uses url_for()
     */
  public function button_to($name, $options = array(), $html_options = null) {
        $html_options = (!is_null($html_options) ? $html_options : array());
        $this->convert_boolean_attributes($html_options, array('disabled'));
        $html_options = $this->convert_confirm_option_to_javascript($html_options);
        if (is_string($options)) {
            $url = $options;
            $name = (!is_null($name) ? $name : $options);
        } else {
            $url = url_for($options);
            $name = (!is_null($name) ? $name : url_for($options));
        }

        $html_options = array_merge($html_options,
                              array("type" => "submit", "value" => $name));
        return "<form method=\"post\" action=\"" .  htmlspecialchars($url)
            . "\" class=\"button-to\"><div>"
            . $this->tag("input", $html_options) . "</div></form>";
  }

    

    /**
     *  Generate URL based on current URL and optional arguments
     *
     *  @param mixed[]
     *  <ul>
     *    <li><b>string:</b><br />
     *      The string value is returned immediately with no
     *      substitutions.</li>
     *    <li><b>array:</b>
     *     <ul>
     *       <li><samp>':controller'=></samp><i>controller value</i></li>
     *       <li><samp>':action'=></samp><i>action value</i></li>
     *       <li><samp>':id'=></samp><i>id value</i></li>
     *     </ul>
     *  </ul>
     *  @return string
     */
  public function url_for($options = array()) {
		$routes_object = new WXRoute;
    $url_base = "/";
    $url = array();
    $extra_params = array();
    if(!is_array($options)) {
      $options=array("action"=>$options);
    }
		
		if(array_key_exists("controller", $options)) {
		  if($options['controller'] == WXConfiguration::get("route/default")) continue;
    	$url[] = $options["controller"];
			unset($options["controller"]);
    } else {
    	$url[] = $routes_object->get_url_controller();
    }
      
    //  If controller found, get action from $options
   	if(array_key_exists("action", $options)) {
    	$url[] = $options["action"];
			unset($options["action"]);
    } 
    
		if(array_key_exists("id", $options)) {
    	$url[] = $options["id"];
			unset($options["id"]);
    }
          
    if(count($options)) {
    	foreach($options as $key => $value) {
      	$extra_params[$key] = $value; 
     	}    
    }

		if(!count($extra_params)) {
		  if(array_pop(array_values($url))=="index") array_pop($url);
    	return $url_base.implode("/", $url)."/";
		} 
    return $url_base . implode("/", $url)."/"
          . (count($extra_params)
             ? "?".http_build_query($extra_params) : null);
    }   

}

?>