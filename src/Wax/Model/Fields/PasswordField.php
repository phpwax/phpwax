<?php
namespace Wax\Model\Fields;
use Wax\Model\Field;


/**
 * PasswordField class
 *
 * @package PHP-Wax
 **/
class PasswordField extends CharField {
  
  public $maxlength = "64";
  public $widget = "PasswordInput";
  public $data_type = "string";

}