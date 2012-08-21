<?php

/**
 * Select Input Widget class
 *
 * @package PHP-Wax
 **/
class SelectInput extends WaxWidget {

  public $null=false;

  public $allowable_attributes = array(
    "name", "disabled", "readonly", "size", "id", "class","tabindex", "multiple", "data-placeholder"
  );


  public $class = "input_field select_field";
  public $label_template = '<label for="%s">%s</label>';
  public $template = '<select %s>%s</select>';


  public function tag_content() {
    $output = "";
    $choice = '<option value="%s"%s>%s</option>';
    if(!$this->choices) $this->choices = $this->get_choices();
    $this->map_choices();
    foreach($this->choices as $value=>$option) {
      $sel = "";
			if(is_numeric($this->value) && $this->value==$value) $sel = ' selected="selected"';
			elseif($this->value==$value) $sel = ' selected="selected"';
      $output .= sprintf($choice, $value, $sel, $option);
    }
    return $output;
  }

  public function get_choices(){
    return $this->bound_data->get_choices();
  }

  public function map_choices() {
    if($this->choices instanceof WaxRecordset) {
      $mapped_choice = array();
      foreach($this->choices as $choice) {
        $mapped_choice[$choice->primval()]=$choice->{$choice->identifier};
      }
      if($this->null !==false) $push[""]=$this->null;
      $this->choices = (array)$push+(array)$mapped_choice;

    }

  }

  public function output_name() {
    if($this->bound_data instanceof ForeignKey) return $this->bound_data->table."[".$this->bound_data->col_name."]";
    return parent::output_name();
  }


} // END class