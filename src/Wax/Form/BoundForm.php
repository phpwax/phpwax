<?php
namespace Wax\Form;

class BoundForm implements iterator {
  
  public $post_data = false;
  public $bound_to_model;
  public $elements = array();
  public $errors = array();
  public $prefix;
  
  public function __construct($model, $post_data, $options=array()) {
    foreach($options as $k=>$v) $this->{$k} = $v;
    if(!$this->prefix) $this->prefix = $model->table; 
    if(!$post_data &&  $_REQUEST[$this->prefix]) $this->post_data = $_REQUEST[$this->prefix];
    $this->bound_to_model = $model;
    foreach($model->columns as $column=>$options) {
      $element = $model->get_col($column);
      $widget_name = $element->widget;
      $widget = new $widget_name($column, $element);
      if($element->editable) $this->elements[$column] = $widget;
    }
  }
  
  public function add_element($name, $field_type, $settings=array()) {
    $widget = new $field_type($name, $settings);
    $this->elements[$name] = $widget;
  }
  
  public function save() {
    if(!$this->is_posted()) return false;
    $associations = array();
    foreach($this->elements as $name=>$el) {
      if(isset($this->post_data[$name])) {
 	      if(!$el->is_association) $this->bound_to_model->{$name} = $el->handle_post($this->post_data[$name]);
 	      else $associations[$name] = $el;
      }
    }
    foreach($associations as $name=>$el) $this->bound_to_model->{$name} = $el->handle_post($this->post_data[$name]);
    $res = $this->bound_to_model->save();
    $this->validate();
    if($this->is_valid()) return $res;
    return $this->is_valid();
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
    if($this->post_data==false) return false;
    foreach($this->elements as $k=>$el) {
      if(isset($this->post_data[$k])) return true;
    }
    return false;
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