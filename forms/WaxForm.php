<?php

/**
 * presumes xhtml 1.0 strict
 * @package PHP-Wax
 **/
class WaxForm implements Iterator {
    
  
  //Validation & Format Options
  public $attributes = array(
    "id"=>"",
    "action"=>"",
    "method"=>"post",
    "class" =>"waxform"
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
   
  public function __construct($model = false, $post_data = false, $options=array()) {
    if(is_array($model)) $options = $model;
    if($model instanceof WaxModel) $this->handler = new WaxBoundForm($model, $post_data, $options);
    else $this->handler = new WaxUnboundForm($model, $post_data, $options);
    $this->setup();
  }
 
  public function add_element($name, $field_type, $settings=array()) {
    $this->handler->add_element($name, $field_type, $settings);
  }

  public function define($name, $field_type, $settings=array()){$this->add_element($name, $field_type, $settings);}
  public function add($name, $field_type, $settings=array()) {$this->add_element($name, $field_type, $settings);}
 
  /*** Handler Methods - Get Passed on to the form handler */
  public function render() {
    foreach($this->handler->elements as $element) $output .= $element->render();
    if($this->submit === true) {
      $sub = new SubmitInput($this->submit_text, array("name"=>$this->form_prefix));
      $output .= $sub->render();
    }
    if($this->form_tags) return sprintf($this->template, $this->make_attributes(), $output);
    else return $output;
  }
  
  public function save() { return $this->handler->save(); }
  public function is_valid() { return $this->handler->is_valid(); }
  
  
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
   
   public function __set($name, $value) {
     if(class_exists($value, false)) $this->handler->elements[$name] = new $value();
   }
   
   public function get($name) {
     return $this->handler->$name->handle_post(post($name));
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

  

} // END class 

