<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class WaxModelField {

  // Database Specific Configuration
  public $field = false;          // How this column is referred to
  public $null = true;           // Can column be null
  public $default = false;       //default value for the column
  public $primary_key = false;  // the primay key field name - der'h
  public $table = false;          // Table name in the storage engine
  public $col_name;               // Actual name in the storage engine

  //Validation & Format Options
  public $maxlength = false;
  public $minlength = false;
  public $choices = false; //for select fields this is an array
  public $text_choices = false; // Store choices as text in database
  public $editable = true; // Only editable options will be displayed in forms
  public $blank = true;
  public $required = false;
  public $unique = false;
  public $show_label = true;
  public $label = false;
  public $help_text = false;
  public $widget="TextInput";
  public $is_association = false; // Distiguishes between standard field and one that links to other models
  public $model = false;
  public $validator = "WaxValidate";
  public $validations = array();
  public $validation_groups = array();

  public $errors = array();

  public $data_type = "string";

  public static $skip_field_delegation_cache = array();

  public function __construct($column, $model, $options = array()) {
    $this->model = $model;
    foreach($options as $option=>$val) $this->$option = $val;
    if(!$this->field) $this->field = $column;
    if(!$this->table) $this->table = $this->model->table;
    if(!$this->col_name) $this->col_name = $this->field;
    $this->setup();
    $this->map_choices();
    $this->setup_validations();
    if(!is_array(self::$skip_field_delegation_cache[get_class($this)])) $this->setup_skip_delegation_cache();
  }

  public function get() {
    return $this->model->row[$this->col_name];
  }

  public function value() {return $this->get();}

  public function set($value) {
    $this->model->row[$this->col_name]=$value;
  }

  public function before_sync() {}
  public function setup() {}
  public function validate(){}
  public function save() {}
  public function delete(){}
  public function output() {
    return $this->get();
  }

  public function add_validations($array){
    $this->validations = array_unique(array_merge($this->validations, $array));
  }

  public function map_choices() {
    if($this->text_choices && is_array($this->choices)) {
      $choices = $this->choices;
      $this->choices = array();
      foreach($choices as $key=>$choice) {
        if(is_numeric($key)) $this->choices[$choice]=$choice;
        else $this->choices[$key]=$choice;
      }
    }
    if($choices instanceof WaxRecordset) {
      foreach($choices as $choice) {
        $this->choices[$choice->primval()]=$choice->{$choice->identifier};
      }
    }
  }

  public function setup_validations() {
    if($this->required) $this->validations[]="required";
    if($this->minlength) $this->validations[]="length";
    if($this->maxlength) $this->validations[]="length";
    if($this->unique) $this->validations[]="model_unique";
  }


  public function is_valid() {
    $this->validate();
    $validator = new $this->validator($this, $this->field);
    $active_groups = $this->model->validation_groups;
    foreach($this->validations as $valid) if((count($active_groups) <= 0) || count(array_intersect((array)$this->validation_groups[$valid], $active_groups))) $validator->add_validation($valid);
    $validator->validate();
    if($validator->is_valid() && (!$this->errors)) return true;
    else $this->errors = array_merge($this->errors,$validator->errors);
    return false;
  }


  protected function add_error($field, $message) {
    if(!in_array($message, (array)$this->errors)) $this->errors[]=$message;
 	}


 	public function __set($name, $value) {
    if($name=="value") $this->set($value);
    else $this->{$name} = $value;
 	}

 	public function __get($value) {
 	  if($value =="value") return $this->output();
 	  else if($value =="name") return $this->table."[".$this->field."]";
    else if($value =="id") return $this->table."_{$this->field}";
    else if($this->model instanceof WaxModel && array_key_exists($value,(array)$this->model->row)) return $this->model->$value;
 	}

  public function setup_skip_delegation_cache(){
    $class = get_class($this);

    //static cache of associations
    if($this->is_association) WaxModelField::$skip_field_delegation_cache[$class]['assoc'] = true;

    //static cache of overridden get methods
    $method = new ReflectionMethod($class, 'get');
    if($method->getDeclaringClass()->name == "WaxModelField") WaxModelField::$skip_field_delegation_cache[$class]['get'] = true;
    else self::$skip_field_delegation_cache[$class]['get'] = false;
  }
} // END class

