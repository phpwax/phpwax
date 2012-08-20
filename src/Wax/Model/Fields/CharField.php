<?php
namespace Wax\Model\Fields;
use Wax\Model\Field;
/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class CharField extends Field {
  
  public $maxlength = "255";
  public $unique = false;
  public $messages = array(
    "unique"=>      "%s has already been taken"
  );
  public $data_type = "string";
  
  public function setup() {}


}