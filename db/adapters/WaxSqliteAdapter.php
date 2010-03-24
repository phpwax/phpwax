<?php
/**
 * SQLite Adapter class
 *
 * @package PhpWax
 **/
class  WaxSqliteAdapter extends WaxDbAdapter {
  protected $date = 'CURDATE()';
	protected $timestamp = 'date("now")'; 
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
	
	public function connect($db_settings) {
	  $dsn = "{$db_settings['dbtype']}:".WAX_ROOT."{$db_settings['database']}";
	  return new PDO( $dsn );
  }
  
  
  
  /**
   * SQL Creation Methods 
   */
  public function select_sql($model) {
    $sql .= "SELECT ";
    if(is_array($model->select_columns) && count($model->select_columns)) 
      $sql.= join(",", $model->select_columns);
    elseif(is_string($model->select_columns)) 
      $sql.=$model->select_columns;
 		else 
 		  $sql.= "*";
 		   
    $sql.= " FROM `{$model->table}`";
    return $sql;
   }
   
   public function row_count_query($model) {
     if($model->is_paginated) {
       $extrastmt = $this->db->prepare($this->sql_without_limit);
 		   $sth = $this->exec($extrastmt);
 		   $found = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
 		   $this->total_without_limits = count($found);
 	   }
   }
  
  
  
  
  /**
   * Introspection and structure creation methods
   *
   */
  
  public function view_table(WaxModel $model) {
    $stmt = $this->db->prepare("SELECT name FROM sqlite_master WHERE type = 'table'");
    return $this->exec($stmt)->fetchAll(PDO::FETCH_NUM);
  }
  
  public function view_columns(WaxModel $model) {
    $stmt = $this->db->prepare("PRAGMA table_info(`{$model->table}`)");
    $stmt = $this->exec($stmt);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  
  public function create_table(WaxModel $model) {
    $sql = "CREATE TABLE `{$model->table}` (";
    $sql .= $this->column_sql($model->get_col($model->primary_key), $model);
    $sql.=")";
    $stmt = $this->db->prepare($sql);
    $this->exec($stmt);
    return "Created table {$model->table}";
  }
  
  public function add_column(WaxModelField $field, WaxModel $model, $swallow_errors=false) {
    if(!$field->col_name) return true;
    $sql = "ALTER TABLE `$model->table` ADD ";
    $sql.= $this->column_sql($field, $model);
    $stmt = $this->db->prepare($sql);
    $this->exec($stmt, array(), $swallow_errors);
    return "Added column {$field->col_name} to {$model->table}";
  }
  
  public function alter_column(WaxModelField $field, WaxModel $model, $swallow_errors=false) {
    return "SQLite cannot alter {$field->col_name} column on {$model->table} table please adjust your schema manually";
  }
  
  public function column_sql(WaxModelField $field, WaxModel $model) {
    $sql.= "`{$field->col_name}`";
    if(!$type = $field->data_type) $type = "string";
    $sql.=" ".$this->data_types[$type];
    if($field->null ===true) $sql.=" NULL";
    else $sql.=" NOT NULL";
    if($field->default !==false) {$sql.= " DEFAULT '{$field->default}'";}
    if($field->primary) $sql.=" PRIMARY KEY";
    if($field->auto) $sql.= " AUTOINCREMENT";
    return $sql;
  }
  
  
  public function syncdb(WaxModel $model) {
    if(in_array(get_class($model), array("WaxModel", "WaxTreeModel"))) return;
    // First check the table for this model exists
    $tables = $this->view_table($model);
    $exists = false;
    foreach($tables as $table) {
      if($table[0]== $model->table) $exists=true;
    }
    if(!$exists) $output .= $this->create_table($model)."\n";
    
    // Then fetch the existing columns from the database
    $db_cols = $this->view_columns($model);
    // Map definitions to database - create or alter if required

    foreach($model->columns as $model_col=>$model_col_setup) {
      $model_field = $model->get_col($model_col);
      if($info = $model_field->before_sync()) $output .= $info;
      $col_exists = false;
      $col_changed = false;
      foreach($db_cols as $col) {
        if($col["name"]==$model_field->col_name) {
          $col_exists = true;
          if($col["dflt_value"] !== $model_field->default) $col_changed = "default";
          if($col["notnull"] && $model_field->null) $col_changed = "now null";
          if(!$col["notnull"] && !$model_field->null) $col_changed = "now not null";
        }
      }
      if($col_exists==false) $output .= $this->add_column($model_field, $model, true)."\n";
      if($col_changed) $output .= $this->alter_column($model_field, $model, true)." ".$col_changed."\n";
    }
    $output .= "Table {$model->table} is now synchronised";
    $this->db = false;
    $this->db = $this->connect($this->db_settings);
    return $output;
  }
	
	
} // END class