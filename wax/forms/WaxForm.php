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
    "id"="",
    "action"=>"",
    "method"=>"post",
    "encoding"=>""
  );
  public $submit = true;
  public $submit_text = "Submit";
  public $template = '<form %s>%s</form>';
  public $elements = array();
  
  

  public function __construct(WaxModel $model = null) {
    if($model) {
      foreach($model->columns as $column=>$options) {
        $el = $model->get_col($column);
        $widg = $el->widget;
        $widget = new $widg($column, $model);
        if($el->editable) $this->elements[$column] = $widget;
      }
    }
  }
  
  public function render($el_divider = false) {
    $output .="";
    foreach($this->elements as $el) {
      $output.= $el->render();
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

  

} // END class 

