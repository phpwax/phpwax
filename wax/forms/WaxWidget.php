<?php


/**
 * Base Widget class
 *
 * @package PHP-Wax
 **/
class WaxWidget {

  public $attributes = array();
  public $value = false;
  public $choices = false;
  public $blank = true;
  public $label = false;
  public $help_text = false;
  public $show_label = true;
  public $label_template = '<label for="%s>%s</label>';
  public $template = '<input %s />"';
  
  public function __construct($name, WaxModel $model=null) {
    if($model) {
      $this->attribute("name", "[$model->table]$name");
      $this->attribute("id", "{$model->table}_{$name}");
      $this->value = $model->$name;
    }
  }
  
  
  public function render() {
    $out ="";
    if($this->show_label) $out .= sprintf($this->label_template, $this->attributes["id"], $this->label); 
    $out .= sprintf($this->template, $this->make_attributes());
    return $out;
  }
  
  public function attribute($name, $value) {
    $this->attributes[$name]=$value;
  }
  
  public function make_attributes() {
    $res = "";
    foreach($this->attributes as $name=>$value) {
      $res.=sprintf('%s="%s" ', $name, $value);
    }
    return $res;
  }
  



} // END class 