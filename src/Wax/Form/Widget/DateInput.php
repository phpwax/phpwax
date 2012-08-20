<?php
namespace Wax\Form\Widget;

/**
 * Date Input Widget class
 *
 * @package PHP-Wax
 **/
class DateInput extends TextInput {

  public $type="text";
  public $class = "input_field text_field date_field form_datepicker";
  public $validations = array("datetime");
  

} // END class
?>