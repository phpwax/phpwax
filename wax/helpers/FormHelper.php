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
		if(empty($label_name)) {
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
	  if($with_label) $html.= $this->make_label();
	  $html.= $this->to_input_field_tag("text", $options);
		return $html;
	}
	
	function password_field($options = array(), $with_label=true, $after_content="<br />") {
	  if($with_label) $html.= $this->make_label();
	  $html.= $this->to_input_field_tag("password", $options);
		return $html;
	}
	
	function hidden_field($options = array()) {
	  $html = $this->to_input_field_tag("hidden", $options);
		return $html;
	}
	
	function file_field($options = array(), $with_label=true, $after_content="<br />") {
	  if($with_label) $html.= $this->make_label();
	  $html.= $this->to_input_field_tag("file", $options);
		return $html;
	}
	
	function text_area($options = array(), $with_label=true, $after_content="<br />") {
 		if (!array_key_exists("cols", $options)) $options["cols"]=50;
 		if (!array_key_exists("rows", $options)) $options["cols"]=10;
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
	  $options['id']    = $this->object_name . "_" . $this->attribute_name;
		$options["value"] = $this->object->{$this->attribute_name};
	  if($with_label) $html.= $this->make_label();
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

/**
 *  Generate HTML/XML for <input type="text" /> in a view file
 *
 *  Example: In the view file, code
 *           <code><?= text_field("Person", "fname"); ?></code>
 *  Result: <input id="Person_fname" name="Person[fname]" size="30" type="text" value="$Person->fname" />
 *  @param string  Class name of the object being processed
 *  @param string  Name of attribute in the object being processed
 *  @param string[]  Attributes to apply to the generated input tag as:<br>
 *    <samp>array('attr1' => 'value1'[, 'attr2' => 'value2']...)</samp>
 *  @uses FormHelper::to_input_field_tag()
 */
function text_field()  {
  $args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
  return call_user_func_array(array($helper, 'text_field'), $args); 
}

/**
 *  Works just like text_field, but returns a input tag of the "password" type instead.
 * Example: password_field("user", "password");
 *  Result: <input type="password" id="user_password" name="user[password]" value="$user->password" />
 *  @uses FormHelper::to_input_field_tag()
 */
function password_field($object, $field, $options = array()) {
    $args = func_get_args();
		$helper = new FormHelper($args[0], $args[1]);
		array_shift($args); array_shift($args);
	  return call_user_func_array(array($helper, 'password_field'), $args);
}

/**
 *  Works just like text_field, but returns a input tag of the "hidden" type instead.
 *  Example: hidden_field("post", "title");
 *  Result: <input type="hidden" id="post_title" name="post[title]" value="$post->title" />
 *  @uses FormHelper::to_input_field_tag()
 */
function hidden_field($object, $field, $options = array()) {
  $args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
  return call_user_func_array(array($helper, 'hidden_field'), $args);
}

/**
 * Works just like text_field, but returns a input tag of the "file" type instead, which won't have any default value.
 *  @uses FormHelper::to_input_field_tag()
 */
function file_field($object, $field, $options = array()) {
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

/**
 *  Example: text_area("post", "body", array("cols" => 20, "rows" => 40));
 *  Result: <textarea cols="20" rows="40" id="post_body" name="post[body]">$post->body</textarea>
 *  @uses FormHelper::to_text_area_tag()
 */
function text_area($object, $field, $options = array())  {
	$args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'text_area'), $args);
}


/**
 * Returns a checkbox tag tailored for accessing a specified attribute (identified by $field) on an object
 * assigned to the template (identified by $object). It's intended that $field returns an integer and if that
 * integer is above zero, then the checkbox is checked. Additional $options on the input tag can be passed as an
 * array with $options. The $checked_value defaults to 1 while the default $unchecked_value
 * is set to 0 which is convenient for boolean values. Usually unchecked checkboxes don't post anything.
 * We work around this problem by adding a hidden value with the same name as the checkbox.
#
 * Example: Imagine that $post->validated is 1:
 *   check_box("post", "validated");
 * Result:
 *   <input type="checkbox" id="post_validate" name="post[validated] value="1" checked="checked" />
 *   <input name="post[validated]" type="hidden" value="0" />
#
 * Example: Imagine that $puppy->gooddog is no:
 *   check_box("puppy", "gooddog", array(), "yes", "no");
 * Result:
 *     <input type="checkbox" id="puppy_gooddog" name="puppy[gooddog] value="yes" />
 *     <input name="puppy[gooddog]" type="hidden" value="no" />
   *  @uses FormHelper::to_check_box_tag()
 */
function check_box()  {
	$args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'check_box'), $args);
}

/**
 * Returns a radio button tag for accessing a specified attribute (identified by $field) on an object
 * assigned to the template (identified by $object). If the current value of $field is $tag_value the
 * radio button will be checked. Additional $options on the input tag can be passed as a
 * hash with $options.
 * Example: Imagine that $post->category is "trax":
 *   radio_button("post", "category", "trax");
 *   radio_button("post", "category", "java");
 * Result:
 *     <input type="radio" id="post_category" name="post[category] value="trax" checked="checked" />
 *     <input type="radio" id="post_category" name="post[category] value="java" />
 *  @uses FormHelper::to_radio_button_tag()
 */
function radio_button() {
	$args = func_get_args();
	$helper = new FormHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'radio_button'), $args);
}






?>
