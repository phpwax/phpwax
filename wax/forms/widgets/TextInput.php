<?php


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class TextInput extends WaxWidget {

  public $attributes = array(
    "type"=>"text",
    "class"=>"input_field text_field"
  );
  public $value = false;
  public $choices = false;
  public $blank = true;
  public $label = true;
  public $help_text = false;
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<input %s />';
  
  
  
  public function render() {
    $out ="";
    if($this->error_message) $this->attributes["class"].=" error_field";
    if($this->label) $out .= sprintf($this->label_template, $this->attributes["id"], $this->label); 
    $out .= sprintf($this->template, $this->make_attributes());
    if($this->error_message) $out .= sprintf($this->error_template, $this->error_message);
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
  



} // END class