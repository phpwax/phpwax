<?php


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class SelectInput extends WaxWidget {

  public $attributes = array();
  public $value = false;
  public $choices = false;
  public $blank = true;
  public $label = false;
  public $help_text = false;
  public $show_label = true;
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<select %s >%s</select>';
  
  
  
  public function render() {
    $out ="";
    if($this->show_label) $out .= sprintf($this->label_template, $this->attributes["id"], $this->label); 
    $out .= sprintf($this->template, $this->make_attributes(), $this->make_choices());
    return $out;
  }
  
  public function attribute($name, $value) {
    $this->attributes[$name]=$value;
  }
  
  public function make_attributes() {
    $res = "";
    foreach($this->attributes as $name=>$value) {
      $res.=sprintf('%s="%s" ', $name, $value);
    }
    return $res;
  }
  
  public function make_choices() {
    print_r($this->choices);
    return true;
    $output = "";
    $choice = '<option value="%s"%s>%s</option>';
    foreach($this->choices as $option=>$value) {
      $sel = "";
      if($this->name == $option && $this->value==$value) $sel = 'selected="selected"';
      $ouput .= sprintf($choice, $value, $sel, $option);
    }
    return $output;
  }
  



} // END class