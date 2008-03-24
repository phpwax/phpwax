<?php
/**
 *  class WaxDbAdapter
 *
 * @package PHP-Wax
 **/
abstract class WaxDbAdapter {
  
  protected $columns;
  protected $filters;
  protected $offset;
  protected $limit;
  protected $db;
  protected $date = false;
	protected $timestamp = false;
  
  public function __construct($db_settings=array()) {
    if($db_settings['dbtype']=="none") return false;
    if(!$db_settings['dbtype']) $db['dbtype']="mysql";
    if(!$db_settings['host']) $db['host']="localhost";
    if(!$db_settings['port']) $db['port']="3306";
    
    if(isset($db_settings['socket']) && strlen($db_settings['socket'])>2) {
			$dsn="{$db_settings['dbtype']}:unix_socket={$db_settings['socket']};dbname={$db_settings['database']}"; 
		} else {
			$dsn="{$db_settings['dbtype']}:host={$db_settings['host']};port={$db_settings['port']};dbname={$db_settings['database']}";
		}
		
		$this->db = new PDO( $dsn, $db_settings['username'] , $db_settings['password'] );
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
  }

  public function insert(WaxModel $model) {
    $stmt = $this->db->prepare("INSERT into `{$model->table}` (".join(",", array_keys($model->row)).") 
      VALUES (".join(",", $this->bindings($model->row)).")");
    $result = $stmt->execute($model->row);
  }
  
  public function update(WaxModel $model) {
    $stmt = $this->db->prepare("UPDATE `{$model->table}` SET ".$this->update_values($model->row)
      " WHERE `{$model->table}`.{$model->primary_key} = {$model->row[$model->primary_key]}");
    return $stmt->execute($model->row);
  }
  
  public function delete(WaxModel $model) {
    $stmt = $this->db->prepare("DELETE FROM `{$model->table}` WHERE `{$model->primary_key}`={$model->row[$model->primary_key]}");
    return $stmt->execute();
  }
  
  public function select(WaxModel $model) {
    
  }
  
  public function count(WaxModel $model) {
    
  }
  
  public function sync_db(WaxModel $model) {
    
  }
  
  public function exec($sql) {
    
  }
  
  protected function bindings($array) {
		$params = array();
		foreach( $array as $key=>$value ) {
			$params[":{$key}"] = $value;
		}
    return $params;
  }
  
  protected function update_values($array) {
    foreach( $array as $key=>$value ) {
      $expressions[] ="`{$key}`=:{$key}";
    }
    return join( ', ', $expressions );
  }
  
  


} // END class 


?>