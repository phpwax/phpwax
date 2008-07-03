<?php


/**
 * Date Select Input Widget class
 * Presents a date as a collection of three select inputs
 * @package PHP-Wax
 **/
class DateSelectInput extends TextInput {

  public $type="hidden";
  public $class = "input_field hidden_field";
  


  public function make_select_dropdowns() {
    for($i = 1; $i<=31; $i++) {
      $i = str_pad($i, 2, "0", STR_PAD_LEFT);
      $day[$i]=$i;
    }
    for($i = 1; $i<=12; $i++) {
      $i = str_pad($i, 2, "0", STR_PAD_LEFT);
      $month[$i]=$i;
    }
    for($i = 1900; $i<=2030; $i++) {
      $year[$i]=$i;
    }
    $choice = '<option value="%s"%s>%s</option>';
    
    $output='<select id="'.$this->id.'_day" name="'.$this->id.'_day" class="input_field select_field">';
    foreach($day as $k=>$v) {
      $sel = "";
      if($this->value==$value) $sel = ' selected="selected"';
      $output.=sprintf($choice, $k, $sel, $v);
    }
    $output.="</select>";
    $output='<select id="'.$this->id.'_month" name="'.$this->id.'_month" class="input_field select_field">';
    foreach($month as $k=>$v) {
      $sel = "";
      if($this->value==$value) $sel = ' selected="selected"';
      $output.=sprintf($choice, $k, $sel, $v);
    }
    $output.="</select>";
    $output='<select id="'.$this->id.'_year" name="'.$this->id.'_year" class="input_field select_field">';
    foreach($year as $k=>$v) {
      $sel = "";
      if($this->value==$value) $sel = ' selected="selected"';
      $output.=sprintf($choice, $k, $sel, $v);
    }
    $output.="</select>";
  }
  
  public function after_tag() {
    return $this->make_select_dropdowns();
  }

} // END class