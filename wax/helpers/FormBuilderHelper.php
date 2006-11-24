<?php 

class FormBuilderHelper extends FormHelper {
  
  public $form_heading_content;
  public $form_content="";
  
  public function build_form($members=array(), $action="", $form_options=array()) {
    $form_options = array_merge($form_options, array("class"=>"form_container"));
    foreach($members as $form_item) {
      $this->form_content .= $form_item;
    }
    $this->form_content = $this->content_tag("fieldset", $this->form_content);
    $this->form_content = start_form_tag($action, $form_options).$this->form_content;
    $this->form_content .= "</form>";
    return $this->form_content;
  }
  
  public function form_heading($content) {
    return $this->content_tag("h3", $content);
  }
  
  public function form_divider() {
    return "<hr />";
  }
  
  public function tiny($content, $extra_info=null, $label=null) {
    return $this->make_output("tiny", $content, $extra_info, $label);
  }
  public function small($content, $extra_info=null, $label=null) {
    return $this->make_output("small", $content, $extra_info, $label);
  }
  public function medium($content, $extra_info=null, $label=null) {
    return $this->make_output("medium", $content, $extra_info, $label);
  }
  public function large($content, $extra_info=null, $label=null) {
    return $this->make_output("large", $content, $extra_info, $label);
  }
  
  protected function make_output($class, $content, $extra_info, $label) {
    $div_options["class"] = $class;
    if($label) $output = $this->content_tag("label", $label, array("class"=>"description"));
    $output .= $content;
    if($extra_info) $output = $output.$extra_info;
    return $this->content_tag("div", $output, $div_options);
  }
  
}