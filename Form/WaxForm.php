<?php
namespace Wax\Form;

/**
 * WaxForm class.
 * Allows simplified rendering and validation of forms
 * 
 * Forms can be bound or unbound depending on the presence of a WaxModel class being passed into the constructor.
 * Bound forms save the validated post to an attached model.
 *
 * @package PHP-Wax
 **/
class Form implements Iterator {
    
  
  //Validation & Format Options
  public $attributes = array(
    "id"=>"",
    "action"=>"",
    "method"=>"post",
    "class" =>"waxform",
    "enctype"=>"multipart/form-data"
  );
  public $submit = true;
  public $submit_text = "Submit";
  public $template = '<form %s><fieldset>%s</fieldset></form>';
  public $post_data = false;
  public $bound_to_model = false;
  public $validation_errors = array();
  public $form_tags = true;
  public $form_prefix = false;
  public $handler = false;
  
  /**
   *  The constructor allows switching between bound and unbound.
   *  If $model is an instance of WaxModel the handler will be set to WaxBoundForm 
   **/
  public function __construct($model = false, $post_data = false, $options=array()) {
    if(is_array($model)) $options = $model;
    if($model instanceof WaxModel) $this->handler = new WaxBoundForm($model, $post_data, $options);
    elseif($model instanceof WaxRecordset) $this->handler = new WaxRecordsetForm($model, $post_data, $options);
    else $this->handler = new WaxUnboundForm($model, $post_data, array_merge($options, array('form_prefix'=>$this->form_prefix)) );
		if($this->form_prefix) $this->handler->form_prefix = $this->form_prefix;
    $this->setup();
  }
 
 
 /**
  * Adds an element to the form class
  * @param $field_type is a class that will handle the rendering.
  * @param $settings will be passed on to the new instance of $field_type
  * @return void
  **/

  public function add_element($name, $field_type, $settings=array()) {
    $this->handler->add_element($name, $field_type, $settings);
  }

  // Alias for add_element
  public function define($name, $field_type, $settings=array()){$this->add_element($name, $field_type, $settings);}
  // Alias for add_element
  public function add($name, $field_type, $settings=array()) {$this->add_element($name, $field_type, $settings);}
 
  /*** Handler Methods - Get Passed on to the form handler */
  public function render($options = array()) {
    foreach($options as $set=>$val) $this->attributes[$set]=$val;
    foreach($this->handler->elements as $element) $output .= $element->render(array('prefix'=>$this->form_prefix));
    if($this->submit === true) {
      $sub = new SubmitInput($this->submit_text, array("prefix"=>$this->form_prefix));
      $output .= $sub->render();
    }
    if($this->form_tags) return sprintf($this->template, $this->make_attributes(), $output);
    else return $output;
  }
  
  public function save() { return $this->handler->save(); }
  public function is_valid() { return $this->handler->is_valid(); }
  public function validate(){ return $this->handler->validate(); }
  
  /* Form rendering methods */
  public function start() {
    return rtrim(sprintf($this->template, $this->make_attributes(), ""), "</form>");
  }
   
  public function __get($name) {
    if($name == "elements") return $this->handler->elements;
    if(array_key_exists($name, $this->handler->elements)) return $this->handler->elements[$name];
  }
  
  public function make_attributes() {
    $res = "";
    if(!$this->attributes['id']) $this->attributes['id'] = $this->form_prefix;
    foreach($this->attributes as $name=>$value) $res.=sprintf('%s="%s" ', $name, $value);
    return $res;
  }
  
  public function errors() {
    if(!$this->handler->errors) return "";
    $output = "<ul class='user_errors'>";
    foreach($this->handler->errors as $k=>$error) 
      foreach($error as $err) $output .= "<li class='error_message'>$err</li>";
    return $output ."</ul>";
  }
  
  public function add_error($error) {
    $this->handler->errors[]=array($error);
  }
   
   public function __set($name, $value) {
     if(class_exists($value, false)) $this->handler->elements[$name] = new $value();
   }
   
   public function get($name) {
     return $this->handler->$name->handle_post(Request::param($name));
   }
   
   public function setup(){}
   
   
   /* Iterator functions */
   
   public function current() {
     return $this->handler->current();
   }

   public function key() {
     return $this->handler->key();
   }

   public function next() {
     return $this->handler->next();
   }


   public function rewind() {
     $this->handler->rewind();
   }

   public function valid() {
     return $this->handler->valid();
   }
   
   
   public function __call($method, $args) {
     return call_user_func_array(array($this->handler, $method), $args);
   }

  

} // END class 

