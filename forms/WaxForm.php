<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class WaxForm implements Iterator {
    
  
  //Validation & Format Options
  public $attributes = array(
    "name"=>"",
    "id"=>"",
    "action"=>"",
    "method"=>"post",
    "encoding"=>"multipart/form-data"
  );
  public $submit = true;
  public $submit_text = "Submit";
  public $template = '<form %s>%s</form>';
  public $elements = array();
  public $post_data = false;
  public $bound_to_model = false;
  
  

  public function __construct($model = false, $post_data = false) {
    if($this->post_data) $this->post_data=$post_data;
    elseif($_POST) $this->post_data = $_POST;
    if($model instanceof WaxModel) {
      if($this->post_data) $this->post_data = $this->post_data[$model->table];
      $this->bound_to_model = $model;
      foreach($model->columns as $column=>$options) {
        $element = $model->get_col($column);
        $widget_name = $element->widget;
        $widget = new $widget_name($column, $element);
        $this->elements[$column] = $widget;
      }
    }
    $this->setup();
  }
  
  public function add_element($name, $field_type, $settings=array()) {
    $widget = new $field_type($name, $settings);
    $this->elements[$name] = $widget;
  }
  
  public function render() {
    $output .="";
    foreach($this->elements as $el) {
      if($el->editable) $output.= $el->render();
    }
    if($this->submit) {
      $submit = new SubmitInput("submit");
      $submit->attribute("value", $this->submit_text);
      $output.= $submit->render();
    }
    return sprintf($this->template, $this->make_attributes(), $output);
  }
  
  public function save() {
    if(!$this->is_valid()) return false;
    if($this->bound_to_model) return $this->handle_post();
    else return $this->post_data;
  }
  
  public function handle_post() {
    foreach($this->elements as $name=>$el) {
      if($this->post_data[$name]) {
        $this->bound_to_model->{$name} = $el->handle_post($this->post_data[$name]);
      }
    }
    return $this->bound_to_model->save();
  }
  
  public function make_attributes() {
     $res = "";
     foreach($this->attributes as $name=>$value) {
       $res.=sprintf('%s="%s" ', $name, $value);
     }
     return $res;
   }
   
   public function is_valid() {
     foreach($this->elements as $el) {
       if($el->errors) return false;
     }
     return true;
   }
   
   public function errors() {
     return $this->bound_to_model->errors;
   }
   
   public function start() {
     return rtrim(sprintf($this->template, $this->make_attributes(), ""), "</form>");
   }
   
   public function __get($name) {
     if(array_key_exists($name, $this->elements)) return $this->elements[$name];
   }
   
   public function __set($name, $value) {
     if(class_exists($value, false)) $this->elements[$name] = new $value();
   }
   
   public function get($name) {
     return $this->$name->handle_post(post($name));
   }
   
   public function setup(){}
   
   
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

  

} // END class 

