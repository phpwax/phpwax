<?php
namespace Wax\Db;

/**
 * TextField class
 *
 * @package PHP-Wax
 **/
class TextField extends Field {
  
  public $maxlength = false;
	public $widget = "TextareaInput";
  public $data_type = "text";


} 
