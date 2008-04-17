<?php


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class TextInput extends WaxWidget {

  public $attributes = array(
    "type"=>"text",
    "class"=>"input_field text_field"
  );
  public $value = false;
  public $choices = false;
  public $blank = true;
  public $label = true;
  public $help_text = false;
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<input %s />';
  


} // END class