<?php
namespace Wax\Form;

/**
 * Base Widget class
 *
 * @package PHP-Wax
 **/
class Widget{

  public $allowable_attributes = array(
    "type", "name", "value", "checked", "disabled", "readonly", "size", "id", "class",
    "maxlength", "src", "alt", "accesskey", "tabindex", "rows", "cols", "multiple"
  );
  
  public $defaults = array("name"=>"","editable"=>true,"value"=>"");
  
  public $show_label = true;
  public $label_template = '<label for="%s>%s</label>';
  public $template = '<input %s />%s';
  public $error_template = '<span class="error_message">%s</span>';
  public $bound_data = false;
  public $validator = "WaxValidate";
  public $validations = array();
  public $errors = array();
  public $prefix = false;
  public $inline_errors = true;
  
  public function __construct($name, $data=false) {
    if($data instanceof WaxModelField){
      $this->bound_data = $data;
      if($this->show_label && !$this->bound_data->label) $this->bound_data->label=Inflections::humanize($this->bound_data->field);
    }else {
      $this->defaults["name"]=$name;
      $this->defaults["id"]=$name;
      if($this->show_label) $this->defaults["label"]=Inflections::humanize($name);
      $settings = array_merge($this->defaults, (array)$data);
      foreach($settings as $set=>$val) $this->{$set}=$val;
      $this->value = $this->value();
    }
    /**
     * the validation details
     */
    
    $this->setup_validations();
    $this->validation_classes();
  }
  
  public function validation_classes() {
    if($this->bound_data instanceof WaxModelField) {
      $this->validations = array_merge($this->bound_data->validations, $this->validations);
    }    
    if(count($this->validations)) $this->add_class("validate");
    foreach($this->validations as $valid) {
      $this->add_class("valid-$valid");
    }
  }
  
  public function setup_validations() {
    if($this->validate) $this->validations = (array)$this->validate;
    if($this->required ===true) $this->validations[]="required";
    if($this->minlength) $this->validations[]="length";
    if($this->maxlength) $this->validations[]="length";
    if(count($this->validations) && $this->bound_data instanceof WaxModelField) $this->bound_data->add_validations($this->validations);
  }
  
  public function is_valid() {
    if($this->bound_data instanceof WaxModelField) {
      $valid = $this->bound_data->is_valid();
      $this->errors = $this->bound_data->errors;
      return $valid;
    }
    $validator = new $this->validator($this, $this->field);
    foreach($this->validations as $valid) $validator->add_validation($valid);
    $validator->validate();
    if($validator->is_valid()) return true;
    else $this->errors = $validator->errors();
    return false;
  }
  
  
  public function render($settings = array(), $force=false) {
    foreach($settings as $set=>$val) $this->{$set}=$val;
    if(!$this->editable && !$force) return false;
    $out ="";
    $out .= $this->before_tag();
    if($this->errors) $this->add_class("error_field");
    if($this->show_label) $out .= $this->label();
    $out .= sprintf($this->template, stripslashes($this->make_attributes()), $this->tag_content());
    if($this->errors && $this->inline_errors){
      foreach($this->errors as $error) $out .= sprintf($this->error_template, $error);
    }
    $out .= $this->after_tag();
    return $out;
  }
  
  public function label() {
    return sprintf($this->label_template, $this->output_id(), $this->label);
  }
  
  public function attribute($name, $value) {
    $this->{$name} = $value;
  }
  
  public function value(){
    if($this->bound_data instanceof WaxModelField) return $this->bound_data->{$this->name};
    elseif($this->post_data) return $this->post_data[$this->name];
    return $this->value;
  }
  
  public function make_attributes() {
    $res = "";
    foreach($this->allowable_attributes as $name) {
      if($name == "name") $res.=sprintf('%s="%s" ', $name, $this->output_name());
      elseif($name == "id") $res.=sprintf('%s="%s" ', $name, $this->output_id());
      elseif($name == "value") $res.=sprintf('%s="%s" ', $name, $this->value);
      elseif($name == "checked" && $this->{$name} == "checked") $res.=sprintf('%s="%s" ', $name, $this->value);
      elseif(isset($this->{$name}) && $name != "checked") $res.=sprintf('%s="%s" ', $name, $this->{$name});
    }
    return $res;
  }
  
  public function output_name() {
    if($this->prefix) return $this->prefix."[".$this->name."]";
    return $this->name;
  }
  
  public function output_id() {
    if($this->prefix) return $this->prefix."_".$this->name;
    return $this->id;
  }
  
  public function add_class($class) {
    $this->class = $this->class . " ".$class;
    $this->class = trim($this->class);
  }
  
  public function remove_class($class) {
    $this->class = str_replace($class, "", $this->class);
    $this->class = trim($this->class);
  }
  
  public function before_tag(){}
  public function after_tag(){}
  public function validate(){}
  public function handle_post($post_val){
    return $post_val;
  }
  public function get_choices(){ return array();}
  
  public function tag_content() {
    return true;
  }
  
  public function __get($value) {
    if($this->bound_data instanceof WaxModelField) return $this->bound_data->{$value};
  }
  
  public function __set($name, $value) {
		if(in_array($name, $this->allowable_attributes)) $this->{$name} = $value;
    elseif($this->bound_data instanceof WaxModelField) {
      $this->bound_data->{$name}=$value;
    } else $this->{$name}=$value;
  }



} // END class