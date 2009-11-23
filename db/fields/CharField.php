<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class CharField extends WaxModelField {
  
  public $maxlength = "255";
  public $unique = false;
  public $messages = array(
    "unique"=>      "%s has already been taken"
  );
  
  public function setup() {}


}