<?php


/**
 * Base Widget class
 *
 * @package PHP-Wax
 **/
class WaxWidget {


  public $attributes = array();
  public $value = false;
  public $choices = false;
  public $blank = true;
  public $label = true;
  public $help_text = false;
  public $label_template = '<label for="%s>%s</label>';
  public $template = '<input %s />';
  public $error_message = false;
  public $error_template = '<span class="error_message">%s</span>';
  
  public function __construct($name, WaxModel $model=null) {
    if($model) {
      $this->attribute("name", "$model->table[$name]");
      $this->attribute("id", "{$model->table}_{$name}");
      $this->value = $model->output_val($name);
      $this->attribute("value", $this->value);
      
      $field = $model->columns[$name];
      $model_field = new $field[0]($name, $model, $field[1]);
      $this->blank = $model_field->blank;
      $this->choices = $model_field->choices;
      $this->label = $model_field->label;
      $this->help_text = $model_field->help_text;
      if($this->label===true) $this->label = Inflections::humanize($name);
      if($er = $model->errors[$name]) $this->error_message = $er;
    }
  }
  
  
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
  
  public function is_valid() {
    if($this->error_message) return true;
    return false;
  }  



} // END class 