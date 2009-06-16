<?php

/**
 * Base Widget class
 *
 * @package PHP-Wax
 **/
class WaxWidget{

  public $allowable_attributes = array(
    "type", "name", "value", "checked", "disabled", "readonly", "size", "id", "class",
    "maxlength", "src", "alt", "accesskey", "tabindex", "rows", "cols", "multiple"
  );
  
  public $defaults = array("name"=>"","editable"=>true,"value"=>"");
  
  public $label_template = '<label for="%s>%s</label>';
  public $template = '<input %s />%s';
  public $error_template = '<span class="error_message">%s</span>';
  public $bound_data = false;
  public $validator = false;
  public $errors = array();
  public $auto_value = true; //fetch data from post automatically on render
  
  public function __construct($name, $data=false) {
    if($data instanceof WaxModelField) $this->bound_data = $data;
    elseif(is_array($data)) {
      $this->defaults["name"]=$name;
      $this->defaults["id"]=$name."_id";
      $this->defaults["label"]=Inflections::humanize($name);
      $settings = array_merge($this->defaults, $data);
      foreach($settings as $datum=>$value) $this->$datum = $value;
    }else {
      $this->name = $name;
      $this->id = $name."_id";
      $this->editable = true;
      $this->label = Inflections::humanize($name);
    }
    /**
     * the validation details
     */
    $this->validator = new WaxValidate($this, $name);
    if(is_array($data['validate'])){
     foreach($data['validate'] as $type) $this->validator->validate($type, $data);
    }elseif($data['validate']) $this->validator->validate($data['validate'], $data);
  }
  
  
  public function render() {
    if(!$this->editable) return false;
    $out ="";
    $out .= $this->before_tag();
    if($this->errors) $this->class.=" error_field";
    if($this->label) $out .= sprintf($this->label_template, $this->id, $this->label); 
    $out .= sprintf($this->template, $this->make_attributes(), $this->tag_content());
    if($this->errors){
      foreach($this->errors as $error) $out .= sprintf($this->error_template, $error);
    }
    $out .= $this->after_tag();
    return $out;
  }
  
  public function attribute($name, $value) {
    $this->{$name} = $value;
  }
  
  public function value(){
    if($this->bound_data) return $this->bound_data->{$this->name};
    else{
      $data = Request::post($this->post_fields['model']);
      $index = $this->post_fields['attribute'];
      return $data[$index];
    }
  }
  
  public function make_attributes() {
    $res = "";
    if(!$this->value && $this->auto_value) $this->value = $this->value();
    foreach($this->allowable_attributes as $name) {
      if($this->{$name}) $res.=sprintf('%s="%s" ', $name, $this->{$name});
    }
    return $res;
  }
  
  public function before_tag(){}
  public function after_tag(){}
  public function handle_post($post_val){
    return $post_val;
  }
  public function get_choices(){ return array();}
  
  public function tag_content() {
    return true;
  }
  
  public function is_valid() {
    if($this->validator->is_valid()) return true;
    else $this->errors = $this->validator->errors();
    return false;
  }
  
  public function __get($value) {
    if(!$this->bound_data) return false;
    else if($this->bound_data instanceof WaxModelField) return $this->bound_data->{$value};
    else if(is_array($this->bound_data)) return $this->bound_data[$value];
  }
  
  public function __set($name, $value) {
		if(in_array($name, $this->allowable_attributes)) $this->{$name} = $value;
    elseif($this->bound_data instanceof WaxModelField) {
      $this->bound_data->{$name}=$value;
    } else $this->{$name}=$value;
  }



} // END class 