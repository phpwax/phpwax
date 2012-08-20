<?php
namespace Wax\Form\Widget;


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
      $timestamp = mktime(0, 0, 0, $i, 1, 2005);
      $month[$i]=date("F", $timestamp);
    }
    for($i = 1900; $i<=2030; $i++) {
      $year[$i]=$i;
    }
    $choice = '<option value="%s"%s>%s</option>';
    $output.='<select id="'.$this->id.'_day" name="'.$this->name.'[day]" class="date_select_day">';
    foreach($day as $k=>$v) {
      $sel = "";
      if(date("j",strtotime($this->value))==$k) $sel = ' selected="selected"';
      $output.=sprintf($choice, $k, $sel, $v);
    }
    $output.="</select>";
    $output.='<select id="'.$this->id.'_month" name="'.$this->name.'[month]" class="date_select_month">';
    foreach($month as $k=>$v) {
      $sel = "";
      if(date("m",strtotime($this->value))==$k) $sel = ' selected="selected"';
      $output.=sprintf($choice, $k, $sel, $v);
    }
    $output.="</select>";
    $output.='<select id="'.$this->id.'_year" name="'.$this->name.'[year]" class="date_select_year">';
    foreach($year as $k=>$v) {
      $sel = "";
      if(date("Y",strtotime($this->value))==$k) $sel = ' selected="selected"';
      $output.=sprintf($choice, $k, $sel, $v);
    }
    $output.="</select>";
    return $output;
  }
  
  public function after_tag() {
    return $this->make_select_dropdowns();
  }
  
  public function handle_post($val) {
    return date("Y-m-d H:i:s", strtotime($val["month"]."/".$val["day"]."/".$val["year"]));
  }

} // END class