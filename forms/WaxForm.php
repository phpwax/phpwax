<?php

/**
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
  public $validation_errors = array();
  

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
    if(!$settings['post_fields']){
      $settings['post_fields']['model'] = Inflections::underscore(get_class($this));
      $settings['post_fields']['attribute'] = $name;
    }
    if(!$settings['label']) $settings['label'] = ucwords($name);
    if(!$settings['id']) $settings['id'] = Inflections::underscore(get_class($this))."_".Inflections::underscore($name);
    
    $name = $settings['post_fields']['model']."[".$settings['post_fields']['attribute']."]";    
    $widget = new $field_type($name, $settings);
    $this->elements[$name] = $widget;
  }
  /**
   * alias of add element to keep this in sync with how WaxModel setup works..
   *
   */
  public function define($name, $field_type, $settings=array()){
    $this->add_element($name, $field_type, $settings);
  }
  
  public function add($name, $field_type, $settings=array()) {$this->add_element($name, $field_type, $settings);}
  
  
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
    if(!is_array($this->post_data)) return false;
    elseif(!$this->bound_to_model) $this->is_valid();
    elseif($this->bound_to_model) return $this->handle_post();
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
       if(!$el->is_valid()) $this->validation_errors[] = $el->errors;
     }
     if(count($this->validation_errors)) return false;
     else return true;
   }
   
   public function errors() {
     if($this->bound_to_model) $this->validation_errors = $this->bound_to_model->errors;
     return $this->validation_errors;
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
   
   public function is_posted(){
     foreach($this->elements as $el) {
       if(isset($_POST[$el->name])) return true;
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

  

} // END class 

