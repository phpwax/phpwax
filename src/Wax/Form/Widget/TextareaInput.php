<?php
namespace Wax\Form\Widget;


/**
 * Textarea Input Widget class
 *
 * @package PHP-Wax
 **/
class TextareaInput extends Widget {

  public $allowable_attributes = array(
    "name", "disabled", "readonly", "id", "class", "accesskey", "tabindex", "rows", "cols"
  );

  public $class = "input_field textarea_field";  
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<textarea %s>%s</textarea>';
  
  
  
  
  
  public function tag_content() {
    return $this->value;
  }



} // END class