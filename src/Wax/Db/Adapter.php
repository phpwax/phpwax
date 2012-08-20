<?php
namespace Wax\Db;
use Wax\Utilities\Log;
use Wax\Core\Event;

/**
 *  class WaxDbAdapter
 *
 * @package PHP-Wax
 **/
abstract class Adapter {
  
  protected $columns = array();
  protected $distinct = false;
  protected $filters = array();
  protected $offset = "0";
  protected $limit = false;
  protected $having = false;
  public $db;
  protected $date = false;
	protected $timestamp = false;
	protected $db_settings;
	public $data_types = array(
	    'string'          => "varchar",
	    'text'            => "longtext",
	    
	    'date'            => "date",
	    'time'            => 'time',
	    'date_and_time'   => "datetime",
	    
	    'integer'         => "int",
	    'decimal'         => "decimal",
	    'float'           => "float"
  );
  
  
  public $sql_without_limit = false;
  public $total_without_limits = false;
	public $default_db_engine = "MyISAM";
	public $default_db_charset = "utf8";
	public $default_db_collate = "utf8_unicode_ci";
  public $query             = FALSE;
  public $query_class       = FALSE;
  
  public function __construct($db_settings=array()) {
    $this->db_settings = $db_settings;
    if($db_settings['dbtype']=="none") return false;
    if(!$db_settings['dbtype']) $db_settings['dbtype']="mysql";
    if(!$db_settings['host']) $db_settings['host']="localhost";
    if(!$db_settings['port']) $db_settings['port']="3306";
    
    $this->db = $this->connect($db_settings);
    $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
  }
  
  public function connect($db_settings) {
    if(isset($db_settings['socket']) && strlen($db_settings['socket'])>2) {
			$dsn="{$db_settings['dbtype']}:unix_socket={$db_settings['socket']};dbname={$db_settings['database']}"; 
		} else {
			$dsn="{$db_settings['dbtype']}:host={$db_settings['host']};port={$db_settings['port']};dbname={$db_settings['database']}";
		}
    $pdo = new \PDO( $dsn, $db_settings['username'] , $db_settings['password'] );
    $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE);
		$pdo->exec("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
		return $pdo;
  }

  
  public function prepare($sql) {
    try { $stmt = $this->db->prepare($sql); Event::run("wax.db_query",$stmt); } 
    catch(\PDOException $e) {
		  $err = $e->getMessage();
		  switch($e->getCode()) {
		    default: 	throw new SqlException( "{$err}", "Error Preparing Database Query", $sql );
		  }
      exit;
		}
		return $stmt;
  }
  
  public function exec($pdo_statement, $bindings = array(), $swallow_errors=false) {
    try {
      Log::log("info", "[DB] ".$pdo_statement->queryString);
      if(count($bindings)) Log::log("info", "[DB] Values:".join($bindings,",") );
			$pdo_statement->execute($bindings);
		} catch(\PDOException $e) {
			$err = $pdo_statement->errorInfo();
			switch($e->getCode()) {
		    case "42S02":
		    case "42S22":
		    ob_start();
        
        foreach(get_declared_classes() as $class) {
          if(!is_subclass_of($class, "Wax\\Model\\Model")) continue;
          if(is_callable([$class,"syncdb"])) {
            $class_obj = new $class;
            $output = $class_obj->syncdb();
            if(strlen($output)) echo $output;
          }
          
        }
        
		    
		    $sync = false; //Forces destruction and flushing of output buffer
		    ob_end_clean();
		    try {
		      $pdo_statement->execute($bindings);
		    } catch(\PDOException $e) {
		      throw new DBStructureException( "{$err[2]}", "Database Structure Error", $pdo_statement->queryString."\n".print_r($bindings,1) );
		    }
		    break;
		    default:
		    Log::log("error", "[DB] ". $err[2]);
		    if(!$swallow_errors) throw new SqlException( "{$err[2]}", "Error Preparing Database Query", $pdo_statement->queryString."\n".print_r($bindings,1) );
		  }
		}
    Event::run("wax.db_query_end",$stmt);
		return $pdo_statement;
  }
  
  /**
   * Raw query method
   * @param string $sql 
   */
  public function query($sql) {
    return $this->db->query($sql);
  }
  
  /**
   * Passes to PDO::quote functionality
   * @param string $string 
   */
  public function quote($string) {
    return $this->db->quote($string);
  }
  
  public function last_id() {
    return $this->db->lastInsertId();
  }
  
  
  public function row_count_query($model) {
    if($model->_is_paginated) {
      $extrastmt = $this->db->prepare("SELECT FOUND_ROWS()");
		  $this->exec($extrastmt);
		  $found = $extrastmt->fetchAll(\PDO::FETCH_ASSOC);
		  return $found[0]['FOUND_ROWS()'];
	  }
  }
  

  
  
  
  
  /**
   * Fultext search on columns
   *
   * @param string $text 
   * @param array $columns 
   * @return $this
   */
  
  public function search($model, $text, $columns=array(), $relevance_floor=0) {
    // First up try to add the fulltext index. Do nothing if errors
    $cols = array_keys($columns);
    $index_name = implode("_", $cols);
    foreach($cols as $col) {
      $sql = "ALTER TABLE `".$model->table."` ADD FULLTEXT ".$col." ($col);";
      $stmt = $this->db->prepare($sql);
      $this->exec($stmt, array(), true);
    }
    $text = $this->db->quote($text);
    // Run the query adding the weighting supplied in the columns array
    $model->_select_columns = "SQL_CALC_FOUND_ROWS * ,(";
    foreach($columns as $name=>$weighting) {
      $model->_select_columns.="($weighting * (MATCH($name) AGAINST ($text)) ) +";
    }
    $model->_select_columns = rtrim($model->_select_columns, "+");
    $model->_select_columns .= ") AS relevance ";
    $model->filter("MATCH(".implode(",", $cols).") AGAINST ($text IN BOOLEAN MODE)");
    $model->_having = "relevance > ".$relevance_floor;
    $model->_order = "relevance DESC";
    // Add an arbitrary limit to force found_rows to run
    if(!$model->_limit) $model->limit(1000);
    return $model;
  }
  
  
  
  
  
  /**
   * Introspection and structure creation methods
   *
   */
  
  public function syncdb($model) {
    
    // First check the table for this model exists
    $tables = $this->view_table($model);
    $exists = false;
    foreach($tables as $table) {
      if($table[0]== $model->table) $exists=true;
    }

    if(!$exists && $info = $this->create_table($model)) $output .= $info."\n";
    
    // Then fetch the existing columns from the database
    $db_cols = $this->view_columns($model);
    // Map definitions to database - create or alter if required	
    foreach($model->fieldset("columns") as $model_col=>$model_col_setup) {
      $model_field = $model->get_col($model_col);
      if($info = $model_field->before_sync()) $output .= $info;
      $col_exists = false;
      $col_changed = false;
      foreach($db_cols as $key=>$col) {
        if($col["COLUMN_NAME"]==$model_field->col_name) {
          $col_exists = true;
          if($col["COLUMN_DEFAULT"] != $model_field->default) $col_changed = "default";
          if($col["IS_NULLABLE"]=="NO" && $model_field->null) $col_changed = "now null";
          if($col["IS_NULLABLE"]=="YES" && !$model_field->null) $col_changed = "now not null";
        }
      }
      if($col_exists==false && ($info =$this->add_column($model_field, $model, true))) $output .= $info;
      if($col_changed && ($info = $this->alter_column($model_field, $model, true))) $output .= $info." ".$col_changed;
    }
    $table = get_class($model);
    $output .= "Table {$table} is now synchronised";
    return $output;
  }
  
  
  public function view_table($model) {
    $stmt = $this->db->prepare("SHOW TABLES");
    return $this->exec($stmt)->fetchAll(\PDO::FETCH_NUM);
  }
  
  public function view_columns($model) {
    $db = $this->db_settings["database"];
    $stmt = $this->db->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA ='{$db}' 
      AND TABLE_NAME = '{$model->table}'");
    $stmt = $this->exec($stmt);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  public function create_table($model) {
    $sql = "CREATE TABLE IF NOT EXISTS `{$model->table}` (";
    $sql .= $this->column_sql($model->get_col($model->primary_key), $model);
    $sql.=") ENGINE=".$this->default_db_engine." DEFAULT CHARSET=".$this->default_db_charset." COLLATE=".$this->default_db_collate;
    $stmt = $this->db->prepare($sql);
    $this->exec($stmt);
    return "Created table {$model->table}";
  }
  
  public function drop_table($table_name) {
    $sql = "DROP TABLE IF EXISTS `$table_name`";
    $stmt = $this->db->prepare($sql);
    $this->exec($stmt);
    return "...removed table $table_name"."\n";
  }
  
  public function column_sql($field, $model) {
    $sql.= "`{$field->col_name}`";
    if(!$type = $field->data_type) $type = "string";
    $sql.=" ".$this->data_types[$type];
    if($type == "string" && !$field->maxlength) $sql.= "(255) ";
    elseif($field->maxlength) $sql.= "({$field->maxlength}) ";
    if($field->null) $sql.=" NULL";
    else $sql.=" NOT NULL";
    if($field->default) $sql.= " DEFAULT '{$field->default}'";
    if($field->auto) $sql.= " AUTO_INCREMENT";
    if($field->primary) $sql.=" PRIMARY KEY";
    return $sql;
  }
  
  public function add_column($field, $model, $swallow_errors=false) {
    if(!$field->col_name) return false;
    $sql = "ALTER TABLE `$model->table` ADD ";
    $sql.= $this->column_sql($field, $model);
    $stmt = $this->db->prepare($sql);
    $this->exec($stmt, array(), $swallow_errors);
    return "Added column {$field->col_name} to {$model->table}\n";
  }
  
  public function alter_column($field, $model, $swallow_errors=false) {
    if(!$field->col_name) return false;
    $sql = "ALTER TABLE `$model->table` MODIFY ";
    $sql.= $this->column_sql($field, $model);
    $stmt = $this->db->prepare($sql);
    $this->exec($stmt, array(), $swallow_errors);
    return "Updated column {$field->field} in {$model->table}\n";
  }
  


} // END class 


