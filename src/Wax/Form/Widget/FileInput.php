<?php
namespace Wax\Form\Widget;


/**
 * File Input Widget class
 *
 * @package PHP-Wax
 **/
class FileInput extends Widget {

  public $type="file";
  public $class = "input_field file_field";

  public $label_template = '<label for="%s">%s</label>';
  public $template = '<input %s />';
  public $show_existing = true;
  public $show_existing_template = '<span class="existing_file_value">%s</span>';
  
  
  public function render($settings = array(), $force=false) {
    $out = parent::render($settings, $force);
    if($this->show_existing) $out.= sprintf($this->show_existing_template, $this->value());
    return $out;
  }
  
  public function value() {
    if($this->bound_data instanceof WaxModelField) {
      $val =  $this->bound_data->value();
      return $val["filename"];
    }
    return parent::value();
  }

} // END class