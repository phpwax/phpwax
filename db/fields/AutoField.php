<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class AutoField extends WaxModelField {
  
  public $null = false;
  public $default = false;
  public $maxlength = "11";
  public $auto = true;
  public $primary = true;
  public $editable = false;
  public $data_type = "integer";

} 
