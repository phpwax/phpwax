<?php
namespace Wax\Form\Widget;

/**
 * Submit Input Widget class
 *
 * @package PHP-Wax
 **/
class SubmitInput extends TextInput {


  public $type="submit";
  public $class = "input_field submit_field";
  public $label_template = '';
  
  public $defaults = array("editable"=>true,"value"=>"Submit");
  
  public function value() {
    return $this->label;
  }
  
} // END class