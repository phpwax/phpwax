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
     *  @uses default_date_options
     *  @uses default_field_options
     *  @uses default_radio_options
     *  @uses default_text_area_options
     */
    function __construct($object="", $attribute_name="") {
    	parent::__construct($object, $attribute_name);
      if(is_object($object) && !$object instanceof WXActiveRecord) {
				$this->object = new $object;
			}  
			$this->object = $object;
      $this->attribute_name = $attribute_name;
			
			$this->object_name = $this->object->table;
    }

      
    
    
    /**
     *  @todo Document this method
     */
    function tag_name() {
        return "{$this->object_name}[{$this->attribute_name}]";
    }

    /**
     *  @todo Document this method
     */
    function tag_name_with_index($index) {
        return "{$this->object_name}[{$index}][{$this->attribute_name}]";
    }

    /**
     *  @todo Document this method
     */
    function tag_id() {
        return "{$this->object_name}_{$this->attribute_name}";
    }

    /**
     *  @todo Document this method
     */
    function tag_id_with_index($index) {
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
    function add_default_name_and_id_and_value($options) 
    {  	
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
    function to_input_field_tag($field_type, $options = array()) {
			if(!$options["size"] && $field_type != "hidden" && $field_type != "submit") $options["size"]=25;
			$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
		  $options['id']    = $this->object_name . "_" . $this->attribute_name;
      $options["type"] = $field_type;
			if(!isset($options["value"]) && $field_type !="file") {
				$options["value"] = $this->object->{$this->attribute_name};
			}
			return $this->tag("input", $options);            
    }

	function make_label($label_name="", $after_content="<br />") {
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
	
	public function db_to_form_field($object, $field, $type) {
		switch(true) {
			case $length = strstr($type, "varchar"): return label_text_field($object, $field); break;
			case $length = strstr($type, "int"): return label_text_field($object, $field, array("size"=>10)); break;
		}
		return false;
	}
	
	function text_field($options = array(), $with_label=true, $after_content="<br />") {
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_input_field_tag("text", $options);
		return $html;
	}
	
	function password_field($options = array(), $with_label=true, $after_content="<br />") {
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_input_field_tag("password", $options);
		return $html;
	}
	
	function hidden_field($options = array()) {
	  $html = $this->to_input_field_tag("hidden", $options);
		return $html;
	}
	
	function file_field($options = array(), $with_label=true, $after_content="<br />") {
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_input_field_tag("file", $options);
		return $html;
	}
	
	function text_area($options = array(), $with_label=true, $after_content="<br />") {
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

	function submit_field($value="Save") {
		$options["value"]= $value;
	  return $this->to_input_field_tag("submit", $options);
	}
	
	function check_box($options = array(), $checked_value = "1", $unchecked_value = "0", $with_label=true, $after_content="") {
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
	
	function radio_button($tag_value, $options = array(), $with_label=true, $after_content="<br />") {
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

/*  End of main class... below are wrapper functions for methods in the main class */


function form_for() {
	$form_helper = new FormHelper();
  $args = func_get_args();
  return call_user_func_array(array($form_helper, 'form_for'), $args);
}


function text_field()  {
  $args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
  return call_user_func_array(array($helper, 'text_field'), $args); 
}


function password_field() {
  $args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'password_field'), $args);
}


function hidden_field() {
  $args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
  return call_user_func_array(array($helper, 'hidden_field'), $args);
}


function file_field() {
	$args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'file_field'), $args);
}

function submit_field() {
	$args = func_get_args();
	$helper = new FormHelper($args[0], "save");
	array_shift($args);
	return call_user_func_array(array($helper, 'submit_field'), $args);
}


function text_area()  {
	$args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'text_area'), $args);
}


function check_box()  {
	$args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'check_box'), $args);
}


function radio_button() {
	$args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'radio_button'), $args);
}






?>
