<?php
namespace Wax\Model\Fields;
use Wax\Model\Field;
use Wax\Template\Helper\Inflections;
use Wax\Model\Model;
use Wax\Model\Recordset;
use Wax\Model\ModelPointer;
use Wax\Core\ObjectProxy;


/**
 * HasManyField class
 *
 * @package PHP-Wax
 **/
class HasManyField extends Field {
  
  public $target_model    = FALSE;
  public $join_field      = FALSE;
  public $editable        = FALSE;
  public $is_association  = TRUE;
  public $eager_loading   = FALSE;
	public $join_order      = FALSE; //specify order of the returned joined objects
  public $widget          = "MultipleSelectInput";
  public $data_type       = "integer";
  public $value           = FALSE;
  public $additive        = TRUE;  // Default behaviour, non destructive writes.
  public $tainted         = FALSE; // set to true when a write operation has been performed.
  
  public function setup() {
    $this->col_name = false;
    $class_name = get_class($this->model);
    if(!$this->target_model) $this->target_model = Inflections::camelize($this->field, true);
    if(!$this->join_field) $this->join_field = Inflections::underscore($class_name)."_".$this->model->primary_key;
  }
  
  public function before_get($object, $field) {
    if($this->value && !$this->tainted) {
      $object->row[$field] = new Recordset($this->value);
      return;
    } elseif(!$this->value) $object->row[$field] = $this->lazy_load($object);
  }
  
  public function after_set($object, $field) {
    if(get_class($object->row[$field]) == $this->target_model){
      $this->handle_set($object->row[$field]);
    }
    if(is_array($object->row[$field]) || $object->row[$field] instanceof Traversable) {
      foreach($object->row[$field] as $item) $this->handle_set($item);
    }
    $object->row[$field] = new Recordset($this->value);
  }
  
  public function handle_set($object) {
    $this->value[] = $object;
    $proxy = new ObjectProxy($this);
    $object->observe("before_save", $proxy);
    $object->observe("after_save", $proxy);
    $this->tainted = TRUE;
  }
  
  public function before_save($object) {
    unset($object->row[$this->field]);
  }
  
  public function after_save($object) {
    $rows = [];
    foreach($this->value as $model) $rows[]=$model->get()->pk();
    $object::$_backend->group_update(new $this->target_model, [$this->join_field=>$object->pk()], $rows);
    $object->row[$this->field] = $this->value;
  }

  
  public function lazy_load($target) {
    $target->filter(array($this->join_field=>$this->model->primval));
    foreach($target->all() as $row) {
      $ids[]=new ObjectProxy($row);
    }
    $this->tainted = FALSE;
    return new Recordset($target, $ids);
  }
  
  
  public function unlink($value = false) {
    if(!$value) $value = $this->get(); //if nothing gets passed in to unlink then unlink everything
    if($value instanceof $this->target_model){
      $value->{$this->join_field} = 0;
      $value->_col_names[$this->join_field] = 1; //cache column names as keys of an array for the adapter to check which cols are allowed to write
      $value->save();
    }
    if($value instanceof Recordset) foreach($value as $row) $this->unlink($row);
  }
  
  

  public function before_sync() {
    if($this->target_model != get_class($this->model)){
      //define a foreign key in the target model and sync that model
      $target_model = get_class($this->model);
   	  $link = new $this->target_model;
   	  $link->define($this->join_field, "ForeignKey", array("col_name" => $this->join_field, "target_model" => $target_model));
   	  $link->syncdb();
    }
  }
  
  public function create($attributes) {
    $model = new $this->target_model();
    $new_model = $model->create($attributes);
    $new_model->{$this->join_field} = $this->model->primval;
    return $new_model;
  }
  
  public function get_choices() {
    $j = new $this->target_model;
    if($this->identifier) $j->identifier = $this->identifier;
    foreach($j->all() as $row) $this->choices[$row->{$row->primary_key}]=$row->{$row->identifier};
    return $this->choices;
  }
  
  public function __call($method, $args) {
    $model = new $this->target_model();
    $model->filter(array($this->join_field=>$this->model->primval));

    return call_user_func_array(array($model, $method), $args);
  }

} 
