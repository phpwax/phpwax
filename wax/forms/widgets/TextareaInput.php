<?php


/**
 * Textarea Input Widget class
 *
 * @package PHP-Wax
 **/
class TextareaInput extends WaxWidget {

  public $allowable_attributes = array(
    "name", "value", "disabled", "readonly", "id", "class", "accesskey", "tabindex", "rows", "cols"
  );

  public $class = "input_field textarea_field";  
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<textarea %s>%s</textarea>';
  
  
  
  public function render() {
    $out ="";
    unset($this->value);
    if($this->label) $out .= sprintf($this->label_template, $this->attributes["id"], $this->label); 
    $out .= sprintf($this->template, $this->make_attributes(), $this->value);
    return $out;
  }
  
  public function tag_content() {
    return $this->value;
  }



} // END class