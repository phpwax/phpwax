<?php


/**
 * Hidden Input Widget class
 *
 * @package PHP-Wax
 **/
class HiddenInput extends TextInput {
  
  public $allowable_attributes = array(
    "name", "value", "checked", "disabled", "readonly", "type","id","class"
  );

  public $type="hidden";
  public $class = "input_field hidden_field";
  public $show_label=false;
  public $template = '<input %s />';


} // END class