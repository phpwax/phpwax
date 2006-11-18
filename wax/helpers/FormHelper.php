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
    
    /**
     *  @todo Document this method
     *  Initialises helper with model and attribute
     */
    function __construct($object="", $attribute_name="") {
      
    }

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
        if(!array_key_exists("value", $options))
        {
          $options['value'] = $this->get_value();
        }
       	if(array_key_exists("index", $options)) {
            $options["name"] = array_key_exists("name", $options)
                ? $options["name"]
                : $this->tag_name_with_index($options["index"]);
            $options["id"] = array_key_exists("id", $options)
                ? $options["id"]
                : $this->tag_id_with_index($options["index"]);
            unset($options["index"]);
        } elseif($this->auto_index) {
            $options["name"] = array_key_exists("name", $options)
                ? $options["name"]
                : $this->tag_name_with_index($this->auto_index);
            $options["id"] = array_key_exists("id", $options)
                ? $options["id"]
                : $this->tag_id_with_index($this->auto_index);
        } else {
            $options["name"] = array_key_exists("name", $options)
                ? $options["name"]
                : $this->tag_name();
            $options["id"] = array_key_exists("id", $options)
                ? $options["id"]
                : $this->tag_id();
        }
        return $options;
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
		if(!$options["size"] && $field_type != "hidden" && $field_type != "submit") $options["size"]=25;
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
	  $options['id']    = $this->object_name . "_" . $this->attribute_name;
    $options["type"] = $field_type;
		if(!isset($options["value"]) && $field_type !="file") {
			$options["value"] = $this->object->{$this->attribute_name};
		}
		return $this->tag("input", $options);            
  }

	protected function make_label($label_name="", $after_content="<br />") {
	  $option = array("for" =>$this->object_name."_".$this->attribute_name);
		if(!is_string($label_name)) {
	    $label_name = $this->attribute_name;
	  }
		return $this->content_tag("label", ucfirst($label_name), $option).$after_content;
	}

	public function form_for($object, $id=null, $options=array(), $exclude=array()) {
		$obj = new $object($id);
		foreach($obj->column_info() as $field=>$type) {
			if($field==$obj->primary_key || in_array($field, $exclude)) continue;
			$html .= $this->content_tag("p", $this->db_to_form_field($object, $field, $type) );
		}
		return $this->content_tag("form", $html, $options);
	}
	
	public function text_field($obj, $att, $options = array(), $with_label=true, $after_content="<br />") {
		$this->initialise($obj, $att);
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_input_field_tag("text", $options);
		return $html;
	}
	
	public function password_field($obj, $att, $options = array(), $with_label=true, $after_content="<br />") {
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
	
	public function file_field($obj, $att, $options = array(), $with_label=true, $after_content="<br />") {
		$this->initialise($obj, $att);
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_input_field_tag("file", $options);
		return $html;
	}
	
	public function text_area($obj, $att, $options = array(), $with_label=true, $after_content="<br />") {
		$this->initialise($obj, $att);
 		if (!array_key_exists("cols", $options)) $options["cols"]=50;
 		if (!array_key_exists("rows", $options)) $options["cols"]=10;
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
	  $options['id']    = $this->object_name . "_" . $this->attribute_name;
		$options["value"] = $this->object->{$this->attribute_name};
	  if($with_label) $html.= $this->make_label($with_label);
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
	
	public function check_box($obj, $att, $options = array(), $checked_value = "1", $unchecked_value = "0", $with_label=true, $after_content="") {
		$this->initialise($obj, $att);
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
  	$options['id']    = $this->object_name . "_" . $this->attribute_name;
	  $options["type"] = "checkbox";
	  if($this->object->{$this->attribute_name}) {        
	  	$options["checked"] = "checked";          
	  } else {
	    unset($options["checked"]);
	  }
		if($with_label) $html.= $this->make_label("", "");
	  $options['value'] = $checked_value;        
	  $html.= $this->tag("input", $options);
		return $html;
	}
	
	public function radio_button($obj, $att, $tag_value, $options = array(), $with_label=true, $after_content="<br />") {
		$this->initialise($obj, $att);
		$options["type"] = "radio";
    $options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
  	$options['id']    = $this->object_name . "_" . $this->attribute_name;
  	if($this->object->{$this->attribute_name} == $tag_value) {        
    	$options["checked"] = "checked";          
    } else {
    	unset($options["checked"]);
    }        
		$options['value'] = $tag_value;
		if($with_label) $html.= $this->make_label("", "");  
		$html.= $this->tag("input", $options);  
    return $html;
	}
	
}





?>
