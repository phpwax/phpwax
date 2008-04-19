<?php


/**
 * Base Widget class
 *
 * @package PHP-Wax
 **/
class WaxWidget {

  public $allowable_attributes = array(
    "type", "name", "value", "checked", "disabled", "readonly", "size", "id", "class",
    "maxlength", "src", "alt", "accesskey", "tabindex", "rows", "cols", "multiple"
  );

  public $label_template = '<label for="%s>%s</label>';
  public $template = '<input %s />';
  public $error_template = '<span class="error_message">%s</span>';
  public $bound_data = false;
  
  public function __construct($name, $data=false) {
    $this->name = $name;
    if($data) $this->bound_data = $data;
  }
  
  /**
   * This function maps data to the elements in the form
   * Possible paramaters are:
   * 1. A WaxModel instance
   * 2. An associative array of values.
   * @param mixed $data 
   * @return void
   */
  
  public function map_data($data) {
    if($data instanceof WaxModel) {
      $this->name = $data->table[$name];
      $this->id=$data->table."_".$name;
      $this->value = $data->output_val($name);
      
      $field = $data->columns[$name];
      $model_field = new $field[0]($name, $data, $field[1]);
      $this->blank = $model_field->blank;
      $this->choices = $model_field->choices;
      $this->label = $model_field->label;
      $this->help_text = $model_field->help_text;
      if($er = $data->errors[$name]) $this->error_messages = (array)$er;
    } elseif (is_array($data)) {
      foreach ($data as $key => $value) {
        $this->$key = $value;
      }
    }
  }
  
  
  public function render() {
    $out ="";
    if($this->error_messages) $this->class.=" error_field";
    if($this->label) $out .= sprintf($this->label_template, $this->id, $this->label); 
    $out .= sprintf($this->template, $this->make_attributes(), $this->tag_content());
    if($this->error_messages) {
      foreach($this->error_messages as $error) $out .= sprintf($this->error_template, $error);
    }
    return $out;
  }
  
  public function attribute($name, $value) {
    $this->$name = $value;
  }
  
  public function make_attributes() {
    $res = "";
    foreach($this->allowable_attributes as $name=>$value) {
      if($this->$name) $res.=sprintf('%s="%s" ', $name, $value);
    }
    return $res;
  }
  
  public function tag_content() {
    return true;
  }
  
  public function is_valid() {
    if(count($this->error_messages)>0) return false;
    return true;
  }
  
  public function __get($value) {
    if(!$this->bound_data) return false;
    if($this->bound_data instanceof WaxModelField) {
      if($value =="name") return $this->bound_data->table."[{$this->bound_data->field}]";
      if($value =="id") return $this->bound_data->table."_{$this->bound_data->field}";
      return $this->bound_data->$value;
    }
  }



} // END class 