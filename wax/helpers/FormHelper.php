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
    function __construct($object_name="", $attribute_name="") 
    {
        parent::__construct($object_name, $attribute_name);
                
        $this->object_name    = $object_name;        
        $this->attribute_name = $attribute_name;
        
        //  Set default attributes for input fields
        $this->default_field_options = 
            array_key_exists('DEFAULT_FIELD_OPTIONS',$GLOBALS)
            ? $GLOBALS['DEFAULT_FIELD_OPTIONS']
            : array("size" => 30);

        //  Set default attributes for radio buttons
        $this->default_radio_options =
            array_key_exists('DEFAULT_RADIO_OPTIONS',$GLOBALS)
            ? $GLOBALS['DEFAULT_RADIO_OPTIONS']
            : array();

        //  Set default attributes for text areas
        $this->default_text_area_options =
            array_key_exists('DEFAULT_TEXT_AREA_OPTIONS',$GLOBALS)
            ? $GLOBALS['DEFAULT_TEXT_AREA_OPTIONS']
            : array("cols" => 40, "rows" => 20);

        //  Set default attributes for dates
        $this->default_date_options =
            array_key_exists('DEFAULT_Date_OPTIONS',$GLOBALS)
            ? $GLOBALS['DEFAULT_DATE_OPTIONS']
            : array(":discard_type" => true);
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
    function to_input_field_tag($field_type, $options = array()) 
    {
      
        $default_size = $this->default_field_options['size'];
               
        $options["size"] = array_key_exists("size", $options)
            ? $options["size"]: $default_size;
            
        $options = array_merge($this->default_field_options, $options);

        if($field_type == "hidden" || $field_type == "submit") {
          unset($options["size"]);
        }
        $options["type"] = $field_type;  
                
        $options = $this->add_default_name_and_id_and_value($options);
        return $this->tag("input", $options);           
       
    }

    /**
     *  @todo Document this method
     *  @uses add_default_name_and_id_and_value()
     */
    function to_radio_button_tag($tag_value, $options = array() ) 
    {
       $options["type"] = "radio";
        
        if($this->get_value() == $tag_value)
        {        
          $options["checked"] = "checked";          
        } 
        else 
        {
            unset($options["checked"]);
        }        

        $options = $this->add_default_name_and_id_and_value($options);
        $options['value'] = $tag_value;        
        return $this->tag("input", $options);
    }

    /**
     *  @todo Document this method
     *  @uses add_default_name_and_id_and_value()
     */
    function to_text_area_tag($options = array()) 
    {
        if (array_key_exists("size", $options)) 
        {
            $size = explode('x', $options["size"]);
            $options["cols"] = reset($size);
            $options["rows"] = end($size);
            unset($options["size"]);
        }
        $options = array_merge($this->default_text_area_options, $options);
        $options = $this->add_default_name_and_id_and_value($options);
        
        return $this->content_tag("textarea", htmlspecialchars($options['value']),$options);
           
    }

    /**
     *  @todo Document this method
     *  @uses add_default_name_and_id_and_value()
     */
  function to_check_box_tag($options = array(), $checked_value = "1", $unchecked_value = "1") {
  	$options["type"] = "checkbox";
    if($this->get_value() == $checked_value) {        
    	$options["checked"] = "checked";          
		} else {
      unset($options["checked"]);
    }        
		$options = $this->add_default_name_and_id_and_value($options);
		$options['value'] = $checked_value;        
		return $this->tag("input", $options);
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
}

function form_for() {
	$form_helper = new FormHelper();
  $args = func_get_args();
  return call_user_func_array(array($form_helper, 'form_for'), $args);
}


/**
 * Creates a label based on the information passed in
 */
function make_label($object, $field, $label_name="", $spacer = "_") {
  $label_for = $object . $spacer . $field;
	if(empty($label_name)) {
    $label_name = $field;
  }
  return "<label for=\"" . $label_for. "\">" . ucfirst($label_name)  . "</label><br />";
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
function text_field($object, $field, $options = array()) 
{
    $form             = new FormHelper($object, $field);
    $options['name']  = $object . "[" . $field . "]" ;
    $options['id']    = $object . "_" . $field;    
    return $form->to_input_field_tag("text", $options);
}

/**
  * Wrapper for text_field - adds label to the front
  */
function label_text_field($object, $field, $options = array(), $label_name="")  {
  $labeled = make_label($object, $field, $label_name) . text_field($object, $field, $options);
  return $labeled;
}


/**
 *  Works just like text_field, but returns a input tag of the "password" type instead.
 * Example: password_field("user", "password");
 *  Result: <input type="password" id="user_password" name="user[password]" value="$user->password" />
 *  @uses FormHelper::to_input_field_tag()
 */
function password_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    $options['name']  = $object . "[" . $field . "]" ;
    $options['id']    = $object . "_" . $field;    
    return $form->to_input_field_tag("password", $options);
}

/**
 *  Wrapper function for password_field - adds a label infront of the password box
 */ 
function label_password_field($object, $field, $options = array(), $label_name="") {
  return make_label($object, $field, $label_name) . password_field($object, $field, $options); 
}


/**
 *  Works just like text_field, but returns a input tag of the "hidden" type instead.
 *  Example: hidden_field("post", "title");
 *  Result: <input type="hidden" id="post_title" name="post[title]" value="$post->title" />
 *  @uses FormHelper::to_input_field_tag()
 */
function hidden_field($object, $field, $options = array()) {
  $form = new FormHelper($object, $field);
  $options['name']  = $object . "[" . $field . "]" ;
  $options['id']    = $object . "_" . $field;    
  return $form->to_input_field_tag("hidden", $options);
}

function label_hidden_field($object, $field, $options = array(), $label_name="") {
  return make_label($object, $field, $label_name) . hidden_field($object, $field, $options); 
}

/**
 * Works just like text_field, but returns a input tag of the "file" type instead, which won't have any default value.
 *  @uses FormHelper::to_input_field_tag()
 */
function file_field($object, $field, $options = array()) {
	$form = new FormHelper($object, $field);
	$options['name']  = $object . "[" . $field . "]" ;
	$options['id']    = $object . "_" . $field;  
	return $form->to_input_field_tag("file", $options);
}

function label_file_field($object, $field, $options = array(), $label_name="") {
  return make_label($object, $field, $label_name) . file_field($object, $field, $options); 
}


/**
 *  Example: text_area("post", "body", array("cols" => 20, "rows" => 40));
 *  Result: <textarea cols="20" rows="40" id="post_body" name="post[body]">$post->body</textarea>
 *  @uses FormHelper::to_text_area_tag()
 */
function text_area($object, $field, $options = array())  {
  $form = new FormHelper($object, $field);
  $options['name']  = $object . "[" . $field . "]" ;
  $options['id']    = $object . "_" . $field;  
  return $form->to_text_area_tag($options);
}

function label_text_area($object, $field, $options = array(), $label_name="")  {
  return make_label($object, $field, $label_name) . text_area($object, $field, $options); 
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
function check_box($object, $field, $options = array(), $checked_value = "1", $unchecked_value = "0")  {
  $form = new FormHelper($object, $field);
  $options['name']  = $object . "[" . $field . "]" ;
  $options['id']    = $object . "_" . $field;  
  return $form->to_check_box_tag($options, $checked_value, $unchecked_value);
}

function label_check_box($object, $field, $options = array(), $checked_value="1", $unchecked_value="0", $label_name="") {
  return make_label($object, $field, $label_name) . check_box($object, $field, $options, $checked_value, $unchecked_value); 
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
function radio_button($object, $field, $tag_value, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_radio_button_tag($tag_value, $options);
}

function label_radio_button($object, $field, $tag_value, $options = array(), $label_name="")  {
  return make_label($object, $field, $label_name) . radio_button($object, $field, $tag_value, $options); 
}

function submit_field($object, $field="", $options=array()) {
  $form = new FormHelper($object, $field);
  return $form->to_input_field_tag("submit", $options);
}


?>
