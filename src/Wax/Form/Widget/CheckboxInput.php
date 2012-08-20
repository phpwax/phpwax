<?php
namespace Wax\Form\Widget;


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class CheckboxInput extends TextInput {

  public $type="checkbox";
  public $class = "input_field checkbox_field";
  
  public $template = '<input %s />';
  public $label_template = '<label for="%s">%s</label>';
  public $checked_value=1;
  public $unchecked_value=0;
  
  public function render($settings = array(), $force=false) {
    foreach($settings as $set=>$val) $this->{$set}=$val;
    if(!$this->editable && !$force) return false;
    $out ="";
    $out .= $this->before_tag();
    if($this->errors) $this->add_class("error_field");
    if($this->unchecked_value !== false){
      $hidden = new HiddenInput($this->name, array("prefix"=>$this->prefix, "value"=>$this->unchecked_value));
      $out.=$hidden->render();
    }
    if($this->value == $this->checked_value) $this->checked="checked";
    else $this->value = $this->checked_value;
    $out .= sprintf($this->template, $this->make_attributes(), $this->tag_content());
    if($this->label && $this->prefix) $out .= sprintf($this->label_template, $this->prefix."_".$this->name, $this->label);
    elseif($this->label) $out .= sprintf($this->label_template, $this->id, $this->label);
    if($this->errors){
      foreach($this->errors as $error) $out .= sprintf($this->error_template, $error);
    }
    $out .= $this->after_tag();
    return $out;
  }
  
  
  public function setup_validations() {    
    if($this->required === true) $this->validations[]="checked";    
    parent::setup_validations();
  }
  

} // END class