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
  public $choice_template = '<option value="%s"%s>%s</option>';
  public $selected_template = ' selected="selected"';

  public function tag_content() {
    $output = "";
    if(!$this->choices) $this->choices = $this->get_choices();
    $this->map_choices();
    foreach($this->choices as $value=>$option) {
      $sel = "";
			if($this->value == $value) $sel = $this->selected_template;
      $output .= sprintf($this->choice_template, $value, $sel, $option);
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
    if($this->prefix) return $this->prefix."[".$this->name."]";
    if($this->name && $this->name != $this->bound_data->table."[".$this->field."]") return $this->name;
    else if($this->bound_data instanceof ForeignKey) return $this->bound_data->table."[".$this->bound_data->col_name."]";
    return parent::output_name();
  }


} // END class