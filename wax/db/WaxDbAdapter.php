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
  protected $having = false;
  protected $db;
  protected $date = false;
	protected $timestamp = false;
	protected $db_settings;
	public $data_types = array(
	    'AutoField'=>         'int',
      'BooleanField'=>      'int',
      'CharField'=>         'varchar',
      'DateField'=>         'date',
      'DateTimeField'=>     'datetime',
      'DecimalField'=>      'decimal',
      'EmailField'=>        'varchar',
      'FileField'=>         'varchar',
      'FilePathField'=>     'varchar',
      'ForeignKey'=>        'int',
      'ImageField'=>        'varchar',
      'IntegerField'=>      'int',
      'IPAddressField'=>    'varchar',
      'PasswordField'=>     'varchar',
      'SlugField'=>         'varchar',
      'TextField'=>         'longtext',
      'TimeField'=>         'time',
			'FloatField'=>				'float'
  );
  public $total_without_limits = false;
  
  public function __construct($db_settings=array()) {
    $this->db_settings = $db_settings;
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
    $stmt = $this->exec($stmt, $model->row);
    
    //changed back to an old method temporarily
    $new = array($model->primary_key => $this->db->lastInsertId());
    return $model->clear()->filter($new)->first();
    
    //$class_name = get_class($model);
    //return new $class_name($this->db->lastInsertId());
	}
  
  public function update(WaxModel $model) {
    $stmt = $this->db->prepare("UPDATE `{$model->table}` SET ".$this->update_values($model->row).
      " WHERE `{$model->table}`.{$model->primary_key} = {$model->row[$model->primary_key]}");
    $this->exec($stmt, $model->row);
    $id = $model->primval;
    return $model;
  }
  
  public function delete(WaxModel $model) {
    $sql .= "DELETE FROM `{$model->table}`";
    if($model->id) $sql .= "WHERE {$model->primary_key}={$model->id}";
    elseif(count($model->filters)) $sql.= " WHERE ".join(" AND ", $model->filters);    
    if($model->order) $sql.= "ORDER BY {$model->order}";
    if($model->limit) $sql.= " LIMIT {$model->limit}";    
    $stmt = $this->db->prepare($sql);
    return $this->exec($stmt);
  }
  
  public function select(WaxModel $model) {
    if($model->sql) {
      $sql = $model->sql;
    } else {
      $sql .= "SELECT ";
      if(count($this->columns)) $sql.= join(",", $this->columns) ;
  		//mysql extra - if limit then record the number of rows found without limits
  		elseif($model->limit > 0) $sql .= "SQL_CALC_FOUND_ROWS *";
      else $sql.= "*";
      $sql.= " FROM `{$model->table}`";
      if(count($model->filters)) $sql.= " WHERE ".join(" AND ", $model->filters); 
    	if($model->group_by) $sql .= " GROUP BY {$model->group_by}"; 
    	if($model->having) $sql .=" HAVING {$model->having}";  
      if($model->order) $sql.= " ORDER BY {$model->order}";
      if($model->limit) $sql.= " LIMIT {$model->offset}, {$model->limit}";
    }
    $stmt = $this->db->prepare($sql);
		//altered to include extra mysql found rows data
		if($model->limit >0 && $this->exec($stmt)){
			$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$extrastmt = $this->db->prepare("SELECT FOUND_ROWS()");
			$this->exec($extrastmt);
			$found = $extrastmt->fetchAll(PDO::FETCH_ASSOC);
			$this->total_without_limits = $found[0]['FOUND_ROWS()'];
			return $res;
		} elseif($this->exec($stmt)) return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  
  /**
   * Fultext search on columns
   *
   * @param string $text 
   * @param array $columns 
   * @return $this
   */
  
  public function search(WaxModel $model, $text, $columns=array()) {
    // First up try to add the fulltext index. Do nothing if errors
    $cols = array_keys($columns);
    $index_name = implode("_", $cols);
    foreach($cols as $col) {
      $sql = "ALTER TABLE `".$model->table."` ADD FULLTEXT ".$col." ($col);";
      $stmt = $this->db->prepare($sql);
      $this->exec($stmt, array(), true);
    }
    // $sql = "ALTER TABLE `".$model->table."` ADD FULLTEXT ".$index_name." (".implode(",", $cols).");";
    // $stmt = $this->db->prepare($sql);
    // $this->exec($stmt, array(), true);
    
    // Run the query adding the weighting supplied in the columns array
    $sql = "SELECT * ,( ";
    foreach($columns as $name=>$weighting) {
      $sql.="($weighting * (MATCH($name) AGAINST ('$text')) ) +";
    }
    $sql = rtrim($sql, "+");
    $sql .= ") AS relevance FROM ".$model->table." WHERE MATCH(".implode(",", $cols).") AGAINST ('$text' IN BOOLEAN MODE)";

    $model->sql = $sql;
    $model->having = "relevance > 0";
    $model->order = "relevance DESC";
    return $model;
  }
  
  
  public function syncdb(WaxModel $model) {
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
      $output .= $model_field->before_sync();
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
      if($col_exists==false) $output .= $this->add_column($model_field, $model, true)."\n";
      if($col_changed) $output .= $this->alter_column($model_field, $model, true)." ".$col_changed."\n";
    }
    $output .= "Table {$model->table} is now synchronised";
    return $output;
  }
  
  
  public function view_table(WaxModel $model) {
    $stmt = $this->db->prepare("SHOW TABLES");
    return $this->exec($stmt)->fetchAll(PDO::FETCH_NUM);
  }
  
  public function view_columns(WaxModel $model) {
    $db = $this->db_settings["database"];
    $stmt = $this->db->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA ='{$db}' 
      AND TABLE_NAME = '{$model->table}'");
    $stmt = $this->exec($stmt);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  
  public function create_table(WaxModel $model) {
    $sql = "CREATE TABLE IF NOT EXISTS `{$model->table}` (";
    $sql .= $this->column_sql($model->get_col($model->primary_key), $model);
    $sql.=")";
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
  
  public function column_sql(WaxModelField $field, WaxModel $model) {
    $sql.= "`{$field->col_name}`";
    $sql.=" ".$this->data_types[get_class($field)];
    if($field->maxlength) $sql.= "({$field->maxlength}) ";
    if($field->null) $sql.=" NULL";
    else $sql.=" NOT NULL";
    if($field->default) $sql.= " DEFAULT '{$field->default}'";
    if($field->auto) $sql.= " AUTO_INCREMENT";
    if($field->primary) $sql.=" PRIMARY KEY";
    return $sql;
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
    if(!$field->col_name) return true;
    $sql = "ALTER TABLE `$model->table` MODIFY ";
    $sql.= $this->column_sql($field, $model);
    $stmt = $this->db->prepare($sql);
    $this->exec($stmt, array(), $swallow_errors);
    return "Updated column {$field->field} in {$model->table}";
  }
  
  public function exec($pdo_statement, $bindings = array(), $swallow_errors=false) {
    try {
      WaxLog::log("info", "[DB] ".$pdo_statement->queryString);
			$pdo_statement->execute($bindings);
		} catch(PDOException $e) {
			$err = $pdo_statement->errorInfo();
			WaxLog::log("error", "[DB]". $err[2]);
      if(!$swallow_errors) throw new WaxSqlException( "{$err[2]}", "Error Preparing Database Query", $sql );
		}
		return $pdo_statement;
  }
  
  public function query($sql) {
    return $this->db->query($sql);
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