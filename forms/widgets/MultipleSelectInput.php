<?php


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

    if($this->value instanceOf WaxRecordset) foreach($this->value->rowset as $r) if((is_array($r) && $v = array_shift($r)) || $v=$r ) $set_values[] = $v;
    else $set_values = array($this->value);

    foreach((array)$this->choices as $value=>$option) {
      $sel = "";
      if(in_array($value, $set_values)) $sel = ' selected="selected"';
      $output .= sprintf($choice, $value, $sel, $option);
    }
    return $output;
  }

  public function output_name() {
    if($this->prefix) return $this->prefix."[".$this->name."][]";
    return $this->name."[]";
  }

  public function handle_post($post_val){
    return array_filter($post_val);
  }

} // END class