<?php
namespace Wax\Form\Widget;


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class TextInput extends Widget {

  public $type="text";
  public $class = "input_field text_field";
  
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<input %s />';
  
} // END class