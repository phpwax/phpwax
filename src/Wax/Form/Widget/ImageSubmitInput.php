<?php
namespace Wax\Form\Widget;

/**
 * Submit Input Widget class
 *
 * @package PHP-Wax
 **/
class ImageSubmitInput extends TextInput {


  public $type="image";
  public $class = "input_field image_submit_field";
  public $label_template = '';
  public $editable =true;
  public $src;


} 