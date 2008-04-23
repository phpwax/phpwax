<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class WaxForm {
    
  
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
  
  

  public function __construct(WaxModel $model = null) {
    if($model) {
      foreach($model->columns as $column=>$options) {
        $element = $model->get_col($column);
        $widget_name = $element->widget;
        $widget = new $widget_name($column, $element);
        $this->elements[$column] = $widget;
      }
    }
  }
  
  public function add_element($name, $field_type) {
    
  }
  
  public function render($el_divider = false) {
    $output .="";
    foreach($this->elements as $el) {
      if($el->editable) $output.= $el->render();
      if($el_divider) $ouput.=$el_divider;
    }
    if($this->submit) {
      $submit = new SubmitInput("submit");
      $submit->attribute("value", $this->submit_text);
      $output.= $submit->render();
    }
    return sprintf($this->template, $this->make_attributes(), $output);
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
   
   public function __get($name) {
     if(array_key_exists($name, $this->elements)) return $this->elements[$name];
   }
   
   public function __set($name, $value) {
     if(class_exists($value)) $this->elements[$name] = new $value;
   }

  

} // END class 

