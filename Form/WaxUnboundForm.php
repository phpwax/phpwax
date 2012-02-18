<?php
namespace Wax\Form;

class UnboundForm implements iterator {
  
  public $post_data;
  public $elements = array();
  public $errors = array();
  public $form_prefix = false;
  
  public function __construct($model, $post_data, $options) {
    foreach($options as $k=>$v) $this->{$k} = $v;
    if(!$post_data && $this->form_prefix) $this->post_data = $_REQUEST[$this->form_prefix];
    elseif(!$post_data) $this->post_data = $_POST;
  }
  
  public function add_element($name, $field_type, $settings=array()) {
    if($this->form_prefix) $settings["prefix"] = $this->form_prefix;
    $settings["post_data"]=$this->post_data;
    $widget = new $field_type($name, $settings);
    $this->elements[$name] = $widget;
  }
  
  public function save() {
    if(!$this->is_posted()) return false;
    $this->validate();
    if(!count($this->errors)) return $this->results();
    return false;
  }
  
  public function validate() {
    foreach($this->elements as $el) {
      if(!$el->is_valid()) $this->errors[] = $el->errors;
    }
  }
  
  public function is_valid() {
    if( count($this->errors)) return false;
    return true;  
  }
  
  public function is_posted(){
    if($this->form_prefix && count($_POST[$this->form_prefix])) return true;
		elseif($this->form_prefix) return false;
    else {
      foreach($this->elements as $el) {
        if(isset($_POST[$el->name])) return true;
      }
    }
    return false;
  }
  
  public function results(){
    $results = array();
    foreach($this->elements as $name=>$el){
      if(strlen($el->value())) $results[$name] = $el->value();
    }
		
    return $results;
  }
  
  /* Iterator functions */
   
   public function current() {
     return current($this->elements);
   }

   public function key() {
     return key($this->elements);
   }

   public function next() {
     return next($this->elements);
   }


   public function rewind() {
     reset($this->elements);
   }

   public function valid() {
     return $this->current() !== false;
   }
  
}