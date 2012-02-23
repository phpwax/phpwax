<?php
namespace Wax\Model;


/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class Field {
    
  // Database Specific Configuration
  public $field       = FALSE;          // How this column is referred to
  public $null        = TRUE;           // Can column be null
  public $default     = FALSE;       //default value for the column  
  public $primary_key = FALSE;  // the primay key field name - der'h
  public $table       = FALSE;          // Table name in the storage engine
  public $col_name    = FALSE;               // Actual name in the storage engine
  
  //Validation & Format Options
  public $maxlength     = FALSE; 
  public $minlength     = FALSE;
  public $choices       = FALSE; //for select fields this is an array
  public $text_choices  = FALSE; // Store choices as text in database
  public $editable      = TRUE; // Only editable options will be displayed in forms
  public $blank         = TRUE; 
  public $required      = FALSE; 
  public $unique        = FALSE;
  public $show_label    = TRUE;
  public $label         = FALSE;
  public $help_text     = FALSE;
  public $widget        ="TextInput";
  public $data_type     = "string";
  

  public function __construct($column, $options = array()) {
    foreach($options as $option=>$val) $this->$option = $val;
    if(!$this->field) $this->field = $column;
    $this->setup();
    $this->map_choices();
  }
  
  public function setup() {
    if(!$this->col_name) $this->col_name = $this->field;
  }
  
  public function notify($event, $object, $field=FALSE) {
    if($event == "before_save")   $this->before_save($object);
    if($event == "after_save")    $this->after_save($object);
    if($event == "before_set")    $this->before_set($object, $field);
    if($event == "after_set")     $this->after_set($object, $field);
    if($event == "before_get")    $this->before_get($object, $field);
    if($event == "after_get")     $this->after_get($object, $field);    
  }
  
  public function before_save($object) {}
  public function after_save($object) {}
  
  public function before_set($object, $field) {}
  public function after_set($object, $field) {}
  
  public function before_get($object, $field) {}
  public function after_get($object, $field) {}
  

  
  public function map_choices() {
    if($this->text_choices && is_array($this->choices)) {
      $choices = $this->choices;
      $this->choices = array();
      foreach($choices as $key=>$choice) {
        if(is_numeric($key)) $this->choices[$choice]=$choice;
        else $this->choices[$key]=$choice;
      }
    }
    if($choices instanceof Recordset) {
      foreach($choices as $choice) {
        $this->choices[$choice->primval()]=$choice->{$choice->identifier};
      }
    }
  }
 	
  

}