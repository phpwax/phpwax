<?php


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class SelectInput extends WaxWidget {

  public $value = false;
  public $class = "input_field select_field";
  public $choices = false;
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<select %s>%s</select>';
  
  
  public function render() {
    unset($this->attributes["value"]);
    $out ="";
    if($this->error_messages) $this->attributes["class"].=" error_field";
    if($this->label) $out .= sprintf($this->label_template, $this->attributes["id"], $this->label); 
    $out .= sprintf($this->template, $this->make_attributes(), $this->make_choices());
    if($this->error_messages) {
      foreach($this->error_messages as $error) $out .= sprintf($this->error_template, $error);
    }
    return $out;
  }
  
  public function make_choices() {
    $output = "";
    $choice = '<option value="%s"%s>%s</option>';
    foreach($this->choices as $value=>$option) {
      $sel = "";
      if($this->value==$value) $sel = ' selected="selected"';
      $output .= sprintf($choice, $value, $sel, $option);
    }
    return $output;
  }
  



} // END class