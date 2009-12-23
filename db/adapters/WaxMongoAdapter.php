<?php
/**
 * SQLite Adapter class
 *
 * @package PhpWax
 **/
class  WaxMongoAdapter extends WaxDbAdapter {

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
  
  public function check_id(WaxModel &$model) {
    if(!$model->{$model->primary_key} && $model->row["_id"] instanceof MongoId) {
      $model->{$model->primary_key} == (string)$model->row["_id"];
      return true;
    }
    $key = new MongoId();
    $model->{$model->primary_key} = (string)$key;
  }
  
  
  public function select(WaxModel $model) {
    $res = $this->db->{$model->table}->find($this->build_filters($model));
    if($model->limit) $res->limit($model->limit);
    return array_values(iterator_to_array($res));
  }
  
  public function insert(WaxModel $model) {
    return $this->update($model);
  }
  
  public function update(WaxModel $model) {
    $this->check_id($model);
    $this->db->{$model->table}->save(&$model->row);
    return $model;
  }
	
	
} // END class