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
class FormHelper extends WXHelpers {

    /**
     *  Default attributes for input fields
     *  @var string[]
     */
  private $default_field_options = array();

    /**
     *  Default attributes for radio buttons
     *  @var string[]
     */
  private $default_radio_options = array();

    /**
     *  Default attributes for text areas
     *  @var string[]
     */
  private $default_text_area_options = array();

    /**
     *  Default attributes for dates
     *  @var string[]
     */
    private $default_date_options = array();

    
    private $errors = array();


    protected function initialise($object="", $attribute_name="") {
      if(!is_object($object)) {
				$this->object = new $object;
			}  
			$this->object = $object;
      $this->attribute_name = $attribute_name;
			
			$this->object_name = $this->object->table;
    }  
    
    
    /**
     *  @todo Document this method
     */
    protected function tag_name() {
        return "{$this->object_name}[{$this->attribute_name}]";
    }

    /**
     *  @todo Document this method
     */
    protected function tag_name_with_index($index) {
        return "{$this->object_name}[{$index}][{$this->attribute_name}]";
    }

    /**
     *  @todo Document this method
     */
    protected function tag_id() {
        return "{$this->object_name}_{$this->attribute_name}";
    }

    /**
     *  @todo Document this method
     */
    protected function tag_id_with_index($index) {
        return "{$this->object_name}_{$index}_{$this->attribute_name}";
    }

    /**
     *  @todo Document this method
     *  @param string[]
     *  @uses auto_index
     *  @uses tag_id
     *  @uses tag_name
     *  @uses tag_id_with_index()
     *  @uses tag_name_with_index()
     */
  protected function add_default_name_and_id_and_value($options) {
    if(isset($options['value']) || $options['type']=="file") return $options;   	
    if($options['value'] = $this->get_value()) {
      return $options;
    } else {
      unset($options["value"]); 
      return $options;
    }
  }

    /**
     *  Generate an HTML or XML input tag with optional attributes
     *
     *  @param string  Type of input field (<samp>'text'</samp>,
     *                 <samp>'password'</samp>, <samp>'hidden'</samp>
     *                 <i>etc.</i>)
     *  @param string[] Attributes to apply to the input tag:<br>
     *    <samp>array('attr1' => 'value1'[, 'attr2' => 'value2']...)</samp>
     *  @return string
     *   <samp><input type="</samp><i>type</i>
     *   <samp>" maxlength="</samp><i>maxlength</i><samp>" .../>\n</samp>
     *  @uses add_default_name_and_id_and_value()
     *  @uses attribute_name
     *  @uses error_wrapping
     *  @uses default_field_options
     *  @uses object()
     *  @uses tag()
     *  @uses value()
     */
  protected function to_input_field_tag($field_type, $options = array()) {
    if(!$options["class"]) $options["class"]="input_field";
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
	  $options['id']    = $this->object_name . "_" . $this->attribute_name;
    $options["type"] = $field_type;
    $options = $this->add_default_name_and_id_and_value($options);
		if(!isset($options["value"]) && $field_type !="file") {
			$options["value"] = $this->object->{$this->attribute_name};
		}
		return $this->tag("input", $options);            
  }

	protected function make_label($label_name="", $options=array()) {
	  $options = array_merge($options, array("for" =>$this->object_name."_".$this->attribute_name));
		if(!is_string($label_name)) {
	    $label_name = $this->attribute_name;
	  }
		return $this->content_tag("label", humanize($label_name), $options);
	}
	
	public function text_field($obj, $att, $options = array(), $with_label=true) {
		$this->initialise($obj, $att);
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_input_field_tag("text", $options);
		return $html;
	}
	
	public function password_field($obj, $att, $options = array(), $with_label=true) {
		$this->initialise($obj, $att);
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_input_field_tag("password", $options);
		return $html;
	}
	
	public function hidden_field($obj, $att, $options = array()) {
		$this->initialise($obj, $att);
	  $html = $this->to_input_field_tag("hidden", $options);
		return $html;
	}
	
	public function file_field($obj, $att, $options = array(), $with_label=true) {
		$this->initialise($obj, $att);
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_input_field_tag("file", $options);
		return $html;
	}
	
	public function text_area($obj, $att, $options = array(), $with_label=true, $force_content=false) {
		$this->initialise($obj, $att);
 		if (!array_key_exists("cols", $options)) $options["cols"]=50;
 		if (!array_key_exists("rows", $options)) $options["cols"]=10;
 		if(!$options["class"]) $options["class"]="input_field";
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
	  $options['id']    = $this->object_name . "_" . $this->attribute_name;
	  if($with_label) $html.= $this->make_label($with_label);
    $options = $this->add_default_name_and_id_and_value($options);
		if(!isset($options["value"])) {
			$options["value"] = $this->object->{$this->attribute_name};
		}
		if(strlen($options["value"])<1 && $force_content) $options["value"]=$force_content;
		$content = $options['value'];
		unset($options["value"]);
    $html.= $this->content_tag("textarea", htmlspecialchars($content),$options);
		return $html;
  }

	public function submit_field($obj, $value="Save") {
	  $this->initialise($obj, "save");
		$options["value"]= $value;
	  return $this->to_input_field_tag("submit", $options);
	}
	
	public function check_box($obj, $att, $options = array(), $checked_value = "1", $unchecked_value = "0", $with_label=true) {
		$this->initialise($obj, $att);
		if(!$options["class"]) $options["class"]="input_field check_box_field";
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
  	$options['id']    = $this->object_name . "_" . $this->attribute_name;
	  $options["type"] = "checkbox";
	  if($this->object->{$this->attribute_name}) {        
	  	$options["checked"] = "checked";          
	  } else {
	    unset($options["checked"]);
	  }
	  $options['value'] = $checked_value;        
	  $html.= $this->tag("input", $options);
	  $html.= $this->tag("input", array("name" => $options["name"], "type" => "hidden", "value" => $unchecked_value));
	  if($with_label) $html.= $this->make_label($with_label, array("class"=>"check_box_label"));
		return $html;
	}
	
	public function radio_button($obj, $att, $tag_value, $options = array(), $with_label=true) {
		$this->initialise($obj, $att);
		if(!$options["class"]) $options["class"]="input_field radio_button_field";
		$options["type"] = "radio";
    $options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
  	$options['id']    = $this->object_name . "_" . $this->attribute_name;
  	if($this->object->{$this->attribute_name} == $tag_value) {        
    	$options["checked"] = "checked";          
    } else {
    	unset($options["checked"]);
    }        
		$options['value'] = $tag_value;
		if($with_label) $html.= $this->make_label($with_label, array("class"=>"radio_button_label"));  
		$html.= $this->tag("input", $options);  
    return $html;
	}
	
}





?>
