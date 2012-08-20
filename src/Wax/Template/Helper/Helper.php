<?php
namespace Wax\Template\Helper;
use Wax\Utilities\CodeGenerator;

/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/

/**
 *  Basic helper functions
 *
 *  A collection of methods used to generate basic HTML/XML.
 */
class Helper {

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
     *  Convert array of tag attribute names and values to string
     *
     *  @param string[] $options 
     *  @return string
     */
    static protected function tag_options($options) {
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
    static protected function convert_options($options = array()) {
        foreach(array('disabled', 'readonly', 'multiple') as $a) {
            self::boolean_attribute($options, $a);
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
    static protected function boolean_attribute(&$options, $attribute) {
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
    static protected function cdata_section($content) {
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
    static protected function tag($name, $options = array(), $open = false) {
      $html = "<$name ";
      $html .= self::tag_options($options);
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
    static protected function content_tag($name, $content, $options = array()) {
        $html = "<$name ";
        $html .= self::tag_options($options);
        $html .= ">$content</$name>";
        return $html."\n";
    }

}


