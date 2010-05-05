<?php


/**
 * Text Input Widget class
 *
 * @package PHP-Wax
 **/
class MultipleSelectInput extends SelectInput {

  public $template = '<select multiple="multiple" %s>%s</select>';

  public function output_name() {
		return parent::output_name()."[]";
	}


} // END class