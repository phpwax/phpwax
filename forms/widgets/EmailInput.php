<?php


/**
 * Email Input Widget class
 *
 * @package PHP-Wax
 **/
class EmailInput extends TextInput {

  public $type="text";
  public $class = "input_field text_field";

  public $label_template = '<label for="%s">%s</label>';
  public $template = '<input %s />';
  public $validations  = array("email");

} // END class