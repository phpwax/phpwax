<?php


/**
 * Textarea Input Widget class
 *
 * @package PHP-Wax
 **/
class TextareaInput extends WaxWidget {


  public $class = "input_field textarea_field";  
  public $value = false;
  public $blank = true;
  public $label = true;
  public $help_text = false;
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<textarea %s>%s</textarea>';
  
  
  
  public function render() {
    $out ="";
    unset($this->value);
    if($this->label) $out .= sprintf($this->label_template, $this->attributes["id"], $this->label); 
    $out .= sprintf($this->template, $this->make_attributes(), $this->value);
    return $out;
  }



} // END class