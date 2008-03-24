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
  protected $sysDate = 'CURDATE()';
	protected $sysTimeStamp = 'NOW()';
  
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
    
  }
  
  public function update(WaxModel $model) {
    
  }
  
  public function delete(WaxModel $model) {
    
  }
  
  public function select(WaxModel $model) {
    
  }
  
  public function count(WaxModel $model) {
    
  }
  
  public function exec($sql) {
    
  }
  
  


} // END class 


?>