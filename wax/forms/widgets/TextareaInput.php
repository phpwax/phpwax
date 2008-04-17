<?php


/**
 * Textarea Input Widget class
 *
 * @package PHP-Wax
 **/
class TextareaInput extends WaxWidget {

  public $attributes = array(
    "type"=>"text",
    "class"=>"input_field textarea_field"
  );
  public $value = false;
  public $blank = true;
  public $label = true;
  public $help_text = false;
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<textarea %s>%s</textarea>';
  
  
  
  public function render() {
    $out ="";
    unset($this->attributes["value"]);
    if($this->label) $out .= sprintf($this->label_template, $this->attributes["id"], $this->label); 
    $out .= sprintf($this->template, $this->make_attributes(), $this->value);
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