<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/

/**
 *  @todo Document this class
 */
class FormTagHelper extends WXHelpers {

    /**
     *  @todo Document this method
     */
    public function form_tag($url_for_options = array(), $options = array()) {
        $html_options = array_merge(array("method" => "post"), $options);

        if(array_key_exists('multipart',$html_options)
	   && $html_options['multipart']) {
            $html_options['enctype'] = "multipart/form-data";
            unset($html_options['multipart']);
        }
        if($url_for_options=="") $html_options["action"]="";
        else $html_options['action'] = url_for($url_for_options);
        return $this->tag("form", $html_options, true);
    }
    
    protected function make_label($id, $label_name="", $options=array()) {
  	  $option = array_merge($options, array("for" =>$id));
  		if(!is_string($label_name) || strlen($label_name) < 2) {
  	    $label_name = $id;
  	  }
  		return $this->content_tag("label", humanize($label_name), $option);
  	}

    /**
     *  @todo Document this method
     *
     */
    public function start_form_tag() {
        $args = func_get_args();
        return call_user_func_array(array($this, 'form_tag'), $args);
    }

    /**
     *  @todo Document this method
     *
     */
    public function select_tag($name, $option_tags = null, $options = array(), $with_label=true) {
      if($with_label) $html = $this->make_label($name, $with_label);
      if(is_array($option_tags)) {
    	  $option_tags = FormOptionsHelper::options_for_select($option_tags);
  	  } else $option_tags = $option_tags;
      return $html.$this->content_tag("select", $option_tags, array_merge(array("name" => $name, "id" => $name, "class"=>"input_field select_field"), $this->convert_options($options)));
    }

    /**
     *  @todo Document this method
     *
     */
    public function text_field_tag($name, $value = null, $options = array(), $with_label=true) {
      if($with_label) {
        if(strlen($with_label)<2) $with_label = $name;
      }
      if(!$options["class"]) $options["class"]="input_field";
  	  if($with_label) $html.= $this->make_label($name, $with_label);
      return $html.$this->tag("input", array_merge(array("type" => "text", "name" => $name, "id" => $name, "value" => $value), $this->convert_options($options)));
    }

    /**
     *  @todo Document this method
     *
     */
    public function hidden_field_tag($name, $value = null, $options = array()) {
        return $this->text_field_tag($name, $value, array_merge($options, array("type" => "hidden")), false);
    }

    /**
     *  @todo Document this method
     *
     */
    public function file_field_tag($name, $options = array(), $with_label=true) {
      return $this->text_field_tag($name, null, array_merge($this->convert_options($options), array("type" => "file")), $with_label);
    }

    /**
     *  @todo Document this method
     *
     */
    public function password_field_tag($name = "password", $value = null, $options = array(), $with_label=true) {
      return $this->text_field_tag($name, $value, array_merge($this->convert_options($options), array("type" => "password")), $with_label);
    }

    /**
     *  @todo Document this method
     *
     */
    public function text_area_tag($name, $content = null, $options = array(), $with_label=true, $force_content=false) {
  		if(strlen($content)<1 && $force_content) $content=$force_content;
      if ($options["size"]) {
        $size = explode('x', $options["size"]);
        $options["cols"] = reset($size);
        $options["rows"] = end($size);
        unset($options["size"]);
      }
      if(!$options["class"]) $options["class"]="input_field textarea_field";
      if($with_label) $html = $this->make_label($name, $with_label);
      return $html.$this->content_tag("textarea", $content, array_merge(array("name" => $name, "id" => $name), $this->convert_options($options)));
    }

    /**
     *  @todo Document this method
     *
     */

    public function check_box_tag($name, $value = "1", $checked = false, $options = array(), $with_label=true) {
        $html_options = array_merge(array("type" => "checkbox", "name" => $name, "id" => $name, "value" => $value), $this->convert_options($options));
        if ($checked) $html_options["checked"] = "checked";
        if(!$html_options["class"]) $html_options["class"]="input_field check_box_field";
        if($with_label) $html = $this->make_label($name, $with_label, array("class"=>"check_box_label"));
        return $this->tag("input", $html_options).$html;
    }

    /**
     *  @todo Document this method
     *
     */
    public function radio_button_tag($name, $value, $checked = false, $options = array(), $with_label=true) {
        $html_options = array_merge(array("type" => "radio", "name" => $name, "id" => $name, "value" => $value), $this->convert_options($options));
        if ($checked) $html_options["checked"] = "checked";
        if(!$html_options["class"]) $html_options["class"]="input_field radio_button_field";
        if($with_label) $html = $this->make_label($name, $with_label, array("class"=>"radio_button_label"));
        return $this->tag("input", $html_options).$html;
    }

    /**
     *  @todo Document this method
     *
     */
    public function submit_tag($value = "Save changes", $options = array()) {
        return $this->tag("input", array_merge(array("type" => "submit", "name" => "commit", "value" => $value), $this->convert_options($options)));
    }

    /**
     *
     *  @todo Document this method
     *  @uses tag()
     */
    public function image_submit_tag($source, $options = array(), $base = "/images/") {
        return $this->tag("input",
			  array_merge(array("type" => "image",
					    "src" => $base.$source),
				      $this->convert_options($options)));
    }

}



?>
