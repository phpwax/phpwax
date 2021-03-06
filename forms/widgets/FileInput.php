<?php


/**
 * File Input Widget class
 *
 * @package PHP-Wax
 **/
class FileInput extends WaxWidget {

  public $type="file";
  public $class = "input_field file_field";

  public $label_template = '<label for="%s">%s</label>';
  public $template = '<input %s />';
  public $show_existing = true;
  public $show_existing_preview = false;
  public $show_existing_template = '<span class="existing_file_value">%s</span>';
  public $show_preview_template = '<span class="existing_file_preview"><img src="%s"></span>';
  public $max_size_template = '<input type="hidden" name="MAX_FILE_SIZE" value="%s" />';
  
  
  public function render($settings = array(), $force=false) {
    $out =  sprintf($this->max_size_template, $this->max_size);
    $out .= parent::render($settings, $force);
    if($this->show_existing_preview && $this->value()) $out.= sprintf($this->show_preview_template, $this->url());
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
  
  public function url() {
    if($this->bound_data instanceof WaxModelField) {
      return  $this->bound_data->url();
    }
    return parent::url();
  }

} // END class