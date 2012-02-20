<?php
namespace Wax\Model\Fields;
use Wax\Model\Field;
use Wax\Model\Model;
use Wax\Model\Recordset;
use Wax\Template\Helper\Inflections;
use Wax\Core\ObjectProxy;

/**
 * ForeignKey class
 *
 * @package PHP-Wax
 **/
class ForeignKey extends Field {
  
  public $maxlength       = "11";
  public $target_model    = FALSE;
  public $widget          = "SelectInput";
  public $choices         = [];
  public $is_association  = true;
  public $data_type       = "integer";
  public $value           = FALSE;
  
  public function setup() {
    if(!$this->target_model) $this->target_model = Inflections::camelize($this->field, true);
    // Overrides naming of field to model_id if col_name is not explicitly set
    if($this->col_name == $this->field){
      $link = new $this->target_model;
      $this->col_name = Inflections::underscore($this->target_model)."_".$link->primary_key;
    }
  }

  public function validate() {
    return true;
  }
  
  public function get() {
    if($this->value) return $this->value->get();
  }
  
  public function set($value) {
    if(is_object($value)) {
      $this->value = new ObjectProxy($value);
      $this->model->observe("before_save", new ObjectProxy($this));
      $this->model->row[$this->field] = $this->value;
    } 
  }
  
  public function notify($event, $object) {
    $object->row[$this->col_name] = $object->row[$this->field]->get()->pk();
    if($this->field !== $this->col_name) unset($object->row[$this->field]);
    $object->schema("set_key", $this->field);
  }
  
  
  
  
  public function save() {
    return true;
    //return $this->set($this->value);
  }
  
  public function get_choices() {
    if($this->choices && $this->choices instanceof Recordset) {
      foreach($this->choices as $row) $choices[$row->{$row->primary_key}]=$row->{$row->identifier};
      $this->choices = $choices;
      return true;
    }
    $link = new $this->target_model;
    $this->choices[""]="Select";
    foreach($link->all() as $row) $this->choices[$row->{$row->primary_key}]=$row->{$row->identifier};
    return $this->choices;
  }
  
  public function __get($name) {
    if($name == "value") return $this->model->{$this->col_name};
    return parent::__get($name);
  }


} 
