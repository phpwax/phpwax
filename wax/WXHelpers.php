<?php
/*
 * @package wxframework.core
 *
 * This class is based in part on the helpers functionality in the PHP on Trax Framework. 
 * For more information, see:
 *  http://phpontrax.com/
 */

/**
 *  Basic helper functions
 *
 *  A collection of methods used to generate basic HTML/XML.
 */
class WXHelpers {

    /**
     *  @var boolean
     */
    public $auto_index;

    /**
     *  Name of a PHP class(?)
     *  @var string
     */
    public $object_name;

    /**
     *  @todo Document this variable
     */
    public $attribute_name;


    /**
     *  Construct a Helpers object
     *
     */
    function __construct() {
    	
    }

  
    /**
     *  Get value of current attribute in the current ActiveRecord object
     *
     *  If there is a value in $_REQUEST[][], return it.
     *  Otherwise fetch the value from the database.
     *  @uses attribute_name
     *  @uses object()
     *  @uses object_name
     *  @uses ActiveRecord::send()
     */
    protected function value() 
    {
        if (array_key_exists($this->object_name, $_REQUEST)
            && array_key_exists($this->attribute_name,
                                 $_REQUEST[$this->object_name])) {
            $value = $_REQUEST[$this->object_name][$this->attribute_name];
        } 
    }
    
    /**
     *  Replacement for value. Merges the session information and posted
     *  
     */
    protected function get_value()
    {
      $name       = $this->object_name;
      $attribute  = $this->attribute_name;      
      $data       = array_merge($_REQUEST, $_SESSION);
      //if the object name is present then get the matching info
      if(!empty($name))
      {
        $request   = $data[$name];
      }
      //otherwise just use the full data array
      else
      {
        $request   = $data;  
      }

      if(isset($request[$attribute]))
      {
        return $request[$attribute];  
      }
      else
      {
        return "";  
      }
     
    }
    
    /**
     *  Convert array of tag attribute names and values to string
     *
     *  @param string[] $options 
     *  @return string
     */
    protected function tag_options($options) {
        if(count($options)) {
            $html = array();
            foreach($options as $key => $value) {
                $html[] = "$key=\"".@htmlspecialchars($value, ENT_COMPAT)."\"";
            }
            sort($html);
            $html = implode(" ", $html);
            return $html;
        } else {
            return '';
        }
    }

    /**
     *  Convert selected attributes to proper XML boolean form
     *
     *  @uses boolean_attribute()
     *  @param string[] $options
     *  @return string[] Input argument with selected attributes converted
     *                   to proper XML boolean form
     */
    protected function convert_options($options = array()) {
        foreach(array('disabled', 'readonly', 'multiple') as $a) {
            $this->boolean_attribute($options, $a);
        }
        return $options;
    }

    /**
     *  Convert an attribute to proper XML boolean form
     *
     *  @param string[] $options
     *  @param string $attribute
     *  @return void Contents of $options have been converted
     */
    protected function boolean_attribute(&$options, $attribute) {
        if(array_key_exists($attribute,$options)
           && $options[$attribute]) {
            $options[$attribute] = $attribute;
        } else {
            unset($options[$attribute]);
        }
    }
    
    /**
     *  Wrap CDATA begin and end tags around argument
     *
     *  Returns a CDATA section for the given content.  CDATA sections
     *  are used to escape blocks of text containing characters which would
     *  otherwise be recognized as markup. CDATA sections begin with the string
     *  <samp><![CDATA[</samp> and end with (and may not contain) the string 
     *  <samp>]]></samp>. 
     *  @param string $content  Content to wrap
     *  @return string          Wrapped argument
     */
    function cdata_section($content) {
        return "<![CDATA[".$content."]]>";
    }    

    /**
     *  Generate an HTML or XML tag with optional attributes and self-ending
     *
     *  <ul>
     *   <li>Example: <samp>tag("br");</samp><br>
     *       Returns: <samp><br  />\n</samp></li>
     *   <li> Example: <samp>tag("div", array("class" => "warning"), true);</samp><br>
     *       Returns: <samp><div class="warning">\n</samp></li>
     *  </ul>
     *  @param string $name      Tag name
     *  @param string[] $options Tag attributes to apply, specified as
     *                  array('attr1' => 'value1'[, 'attr2' => 'value2']...) 
     *  @param boolean $open
     *  <ul>
     *    <li>true =>  make opening tag (end with '>')</li>
     *    <li>false => make self-terminating tag (end with ' \>')</li>
     *  </ul>
     *  @return string The generated tag, followed by "\n"
     *  @uses tag_options()
     */
    function tag($name, $options = array(), $open = false) 
    {
        $html = "<$name ";
        $html .= $this->tag_options($options);
        $html .= $open ? ">" : " />";
        
        return $html."\n";
    }

    /**
     *  Generate an open/close pair of tags with optional attributes and content between
     *
     *  <ul>
     *   <li>Example: <samp>content_tag("p", "Hello world!");</samp><br />
     *       Returns: <samp><p>Hello world!</p>\n</samp><li>
     *   <li>Example:
     *     <samp>content_tag("div",
     *                       content_tag("p", "Hello world!"),
     *                       array("class" => "strong"));</samp><br />
     *     Returns:
     *     <samp><div class="strong"><p>Hello world!</p></div>\n</samp></li>
     *  </ul>
     *  @uses tag_options()
     *  @param string $name    Tag to wrap around $content
     *  @param string $content Text to put between tags
     *  @param string[] $options Tag attributes to apply, specified as
     *                  array('attr1' => 'value1'[, 'attr2' => 'value2']...) 
     *  @return string Text wrapped with tag and attributes,
     *                 followed by "\n"
     */
    function content_tag($name, $content, $options = array()) {
        $html = "<$name ";
        $html .= $this->tag_options($options);
        $html .= ">$content</$name>";
        return $html."\n";
    }
    
    /**
     *
     *  @uses content_tag()
     *  @uses value()
     */    
    function to_content_tag($tag_name, $options = array()) {
        return $this->content_tag($tag_name, $this->value(), $options);
    }

    function error_messages_for($object) {
      if(strpos($object, "_")) {
        $object = camelize($object, 1);
      }
			$class= new $object;
			$errors = $class->get_errors();
			foreach($errors as $error) {
				$html.= $this->content_tag("li", $error['field']." ".$error['message'], array("class"=>"user_error"));
			}
			if(count($errors)>0) return $this->content_tag("ul", $html, array("class"=>"user_errors"));
			return false;
		}
		
  function render_partial($path, $values=array()) {
    if(strpos($path, "/")) {
      $partial = "_".strrchr($path, "/");
      $path = substr(0,strpos($path, "/"));
    } else {
      $partial = "_".$path;
      $path = "";
    }
    return WXControllerBase::view_to_string($partial.$path, $values);
  }

}

/**
 *  Create a Helpers object and call its content_tag() method
 *
 *  @see Helpers::content_tag()
 *  @param string $name    Tag to wrap around $content
 *  @param string $content Text to put between tags
 *  @param string[] $options Tag attributes to apply
 *  @return string Text wrapped with tag and attributes,
 *                 followed by "\n"
 */
function content_tag() {
    $helper = new WXHelpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'content_tag'), $args);
}

/**
 *  Create a Helpers object and call its tag() method
 *
 *  @see Helpers::tag()
 *  @param string $name    Tag name
 *  @param string[] $options Tag attributes to apply
 *  @param boolean $open
 *  <ul>
 *    <li>true =>  make opening tag (end with '>')</li>
 *    <li>false => make self-terminating tag (end with ' \>')</li>
 *  </ul>
 *  @return string The tag, followed by "\n"
 */
function tag() {
    $helper = new WXHelpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'tag'), $args);
}

/**
 *  Create a Helpers object and call its cdata_section() method
 */
function cdata_section() {
    $helper = new WXHelpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'cdata_section'), $args);
}

function error_messages_for() {
	$helper = new WXHelpers();
  $args = func_get_args();
  return call_user_func_array(array($helper, 'error_messages_for'), $args);
}

function render_partial() {
  $helper = new WXHelpers();
  $args = func_get_args();
  return call_user_func_array(array($helper, 'render_partial'), $args);
}

Autoloader::include_from_registry('AssetTagHelper');
Autoloader::include_from_registry('FormHelper');
Autoloader::include_from_registry('FormTagHelper');
Autoloader::include_from_registry('FormOptionsHelper');
Autoloader::include_from_registry('UrlHelper');



?>