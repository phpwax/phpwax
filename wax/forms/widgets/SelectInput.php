<?php


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class SelectInput extends WaxWidget {

  public $allowable_attributes = array(
    "name", "disabled", "readonly", "size", "id", "class","tabindex", "multiple"
  );
  
  
  public $class = "input_field select_field";
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<select %s>%s</select>';

  
  public function tag_content() {
    $output = "";
    $choice = '<option value="%s"%s>%s</option>';
    if(!$this->choices) $this->choices = $this->get_choices();
    foreach($this->choices as $value=>$option) {
      $sel = "";
      if($this->value==$value) $sel = ' selected="selected"';
      $output .= sprintf($choice, $value, $sel, $option);
    }
    return $output;
  }
  



} // END class