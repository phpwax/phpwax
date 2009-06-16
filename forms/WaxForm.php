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
    "method"=>"post"
  );
  public $submit = true;
  public $submit_text = "Submit";
  public $template = '<form %s><fieldset>%s</fieldset></form>';
  public $elements = array();
  public $post_data = false;
  public $bound_to_model = false;
  public $validation_errors = array();
  public $form_tags = true;
  public $form_prefix = false;
   
  public function __construct($model = false, $post_data = false, $options=array()) {
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
    
    if(!$this->bound_to_model){
      foreach($options as $k=>$v) $this->$k = $v;  
      if(!$this->form_prefix) $this->form_prefix = Inflections::underscore(get_class($this));   
    }
    
    $this->setup();
    /**
     * moved the form submit button to here to allow it to be added to the elements list 
     * so it can be validated to see if the form has been posted
     */
    if($this->submit && !$this->bound_to_model) {
      $settings = $this->element_settings('submit');
      $settings['validate'] = "submission";
      $name = $settings['post_fields']['model']."[".$settings['post_fields']['attribute']."]";
      $this->submit = new SubmitInput($name, $settings);
      $this->submit->attribute("value", 'Submit' );
      $this->elements[$name] = $this->submit;
    }
    
  }
  /**
   * new function to return a default array of settings for any
   * new element added via define / add_element
   * main purpose is to prefix name attribute and ids with form_prefix 
   * to make them unique 
   */
  public function element_settings($name, $settings=array()){
    if(!$settings['post_fields']){
      $settings['post_fields']['model'] = $this->form_prefix;
      $settings['post_fields']['attribute'] = $name;
    }
    if(!$settings['label']) $settings['label'] = ucwords($name);
    if(!$settings['id']) $settings['id'] = $settings['post_fields']['model']."_".Inflections::underscore($name);
    return $settings;
  }
  
  public function add_element($name, $field_type, $settings=array()) {
    $settings = $this->element_settings($name, $settings);    
    $name = $settings['post_fields']['model']."[".$settings['post_fields']['attribute']."]";    
    $widget = new $field_type($name, $settings);
    $this->elements[$name] = $widget;
  }
  /**
   * alias of add element to keep this in sync with how WaxModel setup works..
   */
  public function define($name, $field_type, $settings=array()){
    $this->add_element($name, $field_type, $settings);
  }
  public function add($name, $field_type, $settings=array()) {$this->add_element($name, $field_type, $settings);}
  
  
  public function render() {
    $output .="";
    /**
     * for the forms created with new WaxForm remove the submit from the first item in 
     * the array and put it on the bottom, otherwise stick it back on top
     */
    $first = array_shift($this->elements);
    if($first instanceof SubmitInput) array_push($this->elements, $first);
    else array_unshift($this->elements, $first);
    foreach($this->elements as $el) {
      if($el->editable) $output.= $el->render();
    }
    //only add form tags when setting is on    
    if($this->form_tags) return sprintf($this->template, $this->make_attributes(), $output);
    else return $output;
  }
  
  public function save() {
    if(!is_array($this->post_data)) return false;
    elseif(!$this->bound_to_model){
      if($this->is_valid()) return $this->results();
      else return false;
    }
    elseif($this->bound_to_model) return $this->handle_post();
    else return $this->post_data;
  }
  public function results(){
    if($this->bound_to_model) return $this->bound_to_model;
    else{
      $results = array();
      foreach($this->elements as $el){
        if(strlen($el->value())) $results[$el->post_fields['attribute']] = $el->value();
      }
      return $results;
    }
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
     if(!$this->attributes['id']) $this->attributes['id'] = $this->form_prefix;
     foreach($this->attributes as $name=>$value) {
       $res.=sprintf('%s="%s" ', $name, $value);
     }
     return $res;
   }
   
   public function is_valid() {
     //check to see if the form is posted by checking the validity of the submit button
     if($this->submit && !$this->submit->is_valid()) return false;
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
     echo $name;
     if(class_exists($value, false)) $this->elements[$name] = new $value();
   }
   
   public function get($name) {
     return $this->$name->handle_post(post($name));
   }
   
   public function setup(){
    
   }
   
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

