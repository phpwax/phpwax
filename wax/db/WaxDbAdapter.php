<?php
/**
 *  class WaxDbAdapter
 *
 * @package PHP-Wax
 **/
abstract class WaxDbAdapter {
  
  protected $columns = array();
  protected $distinct = false;
  protected $filters = array();
  protected $offset = "0";
  protected $limit = false;
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
      VALUES (".join(",", array_keys($this->bindings($model->row))).")");
    return $this->exec($stmt, $model->row);
  }
  
  public function update(WaxModel $model) {
    $stmt = $this->db->prepare("UPDATE `{$model->table}` SET ".$this->update_values($model->row).
      " WHERE `{$model->table}`.{$model->primary_key} = {$model->row[$model->primary_key]}");
    return $this->exec($stmt, $model->row);
  }
  
  public function delete(WaxModel $model) {
    $stmt = $this->db->prepare("DELETE FROM `{$model->table}` WHERE `{$model->primary_key}`=:{$model->primary_key}");
    return $this->exec($stmt, $model->row);
  }
  
  public function select(WaxModel $model) {
    $sql .= "SELECT ";
    if(count($this->columns)) $sql.= join(",", $this->columns) ;
    else $sql.= "*";
    $sql.= " FROM `{$model->table}`";
    if(count($model->filters)) $sql.= " WHERE ".join(" AND ", $model->filters);
    if($model->order) {
      $sql.= "ORDER BY {$model->order}";
    }
    if($model->limit) $sql.= " LIMIT {$model->offset}, {$model->limit}";
    $stmt = $this->db->prepare($sql);
    if($this->exec($stmt)) return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  
  public function syncdb(WaxModel $model) {
    $sql = "SHOW TABLES FROM `{$model->table}`";
    $stmt = $this->db->prepare($sql);
    print_r($stmt->getColumnMeta("id"));
  }
  
  public function create_table() {
    
  }
  
  public function add_column() {
    
  }
  
  public function alter_column() {
    
  }
  
  public function exec($pdo_statement, $bindings = array()) {
    try {
			$pdo_statement->execute($bindings);
		} catch(PDOException $e) {
			$err = $this->db->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
		}
		return $pdo_statement;
  }
  
  public function quote($string) {
    return $this->db->quote($string);
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