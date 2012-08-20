<?php
namespace Wax\Form\Widget;


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class MultipleSelectInput extends SelectInput {

  public $template = '<select multiple="multiple" %s>%s</select>';

  
  public function tag_content() {
    if(!$this->choices) $this->choices = $this->get_choices();
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