<?php
namespace Wax\Db;
use Wax\Core\Exception;
use Wax\Core\ObjectProxy;

/**
 * Defines how models map to DB Storage
 *
 * @package PHP-WAX
 */
class Schema  {
  
  public static $adapter = FALSE;
  public $table          = FALSE;
  public $keys           = [];    
  public $associations   = [];    
  public $columns        = [];
  public $values         = [];
  
  public function __construct($db_adapter) {
    self::$adapter = $db_adapter;
  }
  
  
  
  public function define($column, $type, $options=array()) {
    if(!$options["target_model"]) $this->keys[] = $column;
    elseif($options["target_model"]) $this->associations[] = $column; 
    $this->columns[$column] = array($type, $options);
  }
  

  public function get_col($name, $model) {
    if($this->values[$name]) return $this->values[$name]->get();
    if(!$this->columns[$name][0]) throw new Exception("Error", $name." is not a valid call");
    $class = $this->columns[$name][0];
    if(!class_exists($class)) $class = "Wax\\Model\\Fields\\".$class;
    $field = new $class($name, $model, $this->columns[$name][1]);
    $this->values[$name] = new ObjectProxy($field);
    return $field;
  }
  
  public function columns() {
    return $this->columns;
  }
  
  public function keys() {
    return $this->keys;
  }
  
  public function set_key($key) {
    $this->keys[] = $key;
  }
  
  public function set_table($table) {
    $this->table = $table;
  }
  
  public function table() {
    return $this->table;
  }
  
  
  public function associations() {
    return $this->associations;
  }
  
  
  
}
