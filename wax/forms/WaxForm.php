<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class WaxForm {
    
  
  //Validation & Format Options
  public $name = "";
  public $id = "";
  public $action = "";
  public $method="post";
  public $encoding = "";
  public $attributes = array();
  
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
    print_r($this); exit;
  }
  
  public function render($el_divider = false) {
    $output .="";
    foreach($this->elements as $el) {
      $output.= $el->render();
      if($el_divider) $ouput.=$el_divider;
    }
    return sprintf($this->template, $this->make_attributes, $output);
  }
  
  public function make_attributes() {
     $res = "";
     foreach($this->attributes as $name=>$value) {
       $res.=sprintf('%s="%s" ', $name, $value);
     }
     return $res;
   }

  

} // END class 

