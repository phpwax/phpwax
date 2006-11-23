<?php 

class FormBuilderHelper extends FormHelper {
  
  public $form_heading_content;
  public $form_content="";
  
  public function build_form($members=array(), $form_options=array()) {
    print_r($members); exit;
    foreach($members as $name=>$input_field) {
      
    }
  }
  
  public function form_heading($content) {
    return $this->content_tag("h2", $content);
  }
  
  public function form_divider() {
    return "<hr />";
  }
  
  public function tiny_text_field_tag($name, $value = null, $options = array()) {
    $div_options["class"] = "tiny";
    return $this->content_tag("div", text_field_tag($name, $value, $options), $div_options);
  }
  public function small_text_field_tag($name, $value = null, $options = array()) {
    return text_field_tag($name, $value, $options);
  }
  public function medium_text_field_tag($name, $value = null, $options = array()) {
    return text_field_tag($name, $value, $options);
  }
  public function large_text_field_tag($name, $value = null, $options = array()) {
    return text_field_tag($name, $value, $options);
  }
  public function small_submit_tag($value="Save changes", $options = array()) {
    return submit_tag($value, $options);
  }
}