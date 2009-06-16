<?php
/**
 * 
 */

class ContactForm extends WaxForm {
  
  public $form_tags = true; //toggle the form & fieldset containing tags on or off.
  
  public function setup(){
    $this->define("name", "TextInput", array('validate'=>array('length', 'required'), 'minlength'=>3, 'maxlength'=>12)); 
    $this->define("email", "TextInput", array('validate'=>'email')); 
    $this->define("telephone", "TextInput");
    $this->define("message", "TextareaInput"); 
  }
  
}

?>