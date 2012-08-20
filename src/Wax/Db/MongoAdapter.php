<?php
namespace Wax\Db;

/**
 * Mongodb Adapter class
 *
 * @package PhpWax
 **/
class  MongoAdapter extends Adapter {

	public $data_types = array(
	  'string'          => "TEXT",
    'text'            => "TEXT",
    'date'            => "date",
    'time'            => 'time',
    'date_and_time'   => "datetime",
    'integer'         => "INTEGER",
    'decimal'         => "decimal",
    'float'           => "float"
  );
  
  public function __construct($db_settings=array()) {
    $this->db_settings = $db_settings;
    if(!$db_settings['host']) $db_settings['host']="localhost";
    if(!$db_settings['port']) $db_settings['port']="27017";
    $this->db = $this->connect($db_settings);
  }
	
	public function connect($db_settings) {
	  if(!class_exists("Mongo")) throw new WaxDbException("Cannot Initialise Database", "Mongo Driver is not installed");
	  $dsn = "{$db_settings['dbtype']}:".WAX_ROOT."{$db_settings['database']}";
	  $con = new Mongo($db_settings["host"].":".$db_settings["port"]);
	  return $con->{$db_settings["database"]};
  }
  
  public function build_filters(WaxModel $model) {
    $query = array();
    foreach($model->filters as $key=>$filter) {
      if($filter["operator"] =="=") $query[$filter["name"]]=$filter["value"];
    }
    return $query;
  }
  
  public function write_pk(WaxModel $model, $rowset) {
    foreach($rowset as &$row) {
      if(!isset($row[$model->primary_key])) {
        $row[$model->primary_key] = (string)$row["_id"];
      }
    }
    return $rowset;
  }
  
  
  public function select(WaxModel $model) {
    $res = $this->db->{$model->table}->find($this->build_filters($model));
    if($model->limit) $res->limit($model->limit);
    $result = array_values(iterator_to_array($res));
    return $this->write_pk($model, $result);
  }
  
  public function insert(WaxModel $model) {
    return $this->update($model);
  }
  
  public function update(WaxModel $model) {
    //list($model_without_joins, $joins) = $this->split_up_cols($model);
    $this->db->{$model->table}->save($model->row);
    return $model;
  }
  
  public function save_associations(WaxModel $model) {
    
  }
	
	
} // END class