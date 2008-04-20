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
  
  
  public function render() {
    $out ="";
    if($this->errors) $this->class.=" error_field";
    if($this->label) $out .= sprintf($this->label_template, $this->id, $this->label); 
    $out .= sprintf($this->template, $this->make_attributes(), $this->tag_content());
    if($this->errors) {
      foreach($this->errors as $error) $out .= sprintf($this->error_template, $error);
    }
    return $out;
  }
  
  public function attribute($name, $value) {
    $this->$name = $value;
  }
  
  public function make_attributes() {
    $res = "";
    foreach($this->allowable_attributes as $name) {
      if($this->{$name}) $res.=sprintf('%s="%s" ', $name, $this->{$name});
    }
    return $res;
  }
  
  public function tag_content() {
    return true;
  }
  
  public function is_valid() {
    if(count($this->errors)>0) return false;
    return true;
  }
  
  public function __get($value) {
    if(!$this->bound_data) return false;
    if($this->bound_data instanceof WaxModelField) {
      if($value =="name") return $this->bound_data->table."[{$this->bound_data->field}]";
      if($value =="id") return $this->bound_data->table."_{$this->bound_data->field}";
      if($value =="value") return $this->bound_data->get();
      error_log($this->name." :: $value");
      return "Hello";
    }
  }



} // END class 