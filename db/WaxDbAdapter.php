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
  public $db;
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
  
  public $operators = array(
    "="=>     " = ",
    "raw"=>   "",
    "!="=>    " != ",
    "~"=>     " LIKE ",
    "in"=>    " IN"
  );
  public $sql_without_limit = false;
  public $total_without_limits = false;
	public $default_db_engine = "MyISAM";
	public $default_db_charset = "utf8";
	public $default_db_collate = "utf8_unicode_ci";
  
  public function __construct($db_settings=array()) {
    $this->db_settings = $db_settings;
    if($db_settings['dbtype']=="none") return false;
    if(!$db_settings['dbtype']) $db_settings['dbtype']="mysql";
    if(!$db_settings['host']) $db_settings['host']="localhost";
    if(!$db_settings['port']) $db_settings['port']="3306";
    
    $this->db = $this->connect($db_settings);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  
  public function connect($db_settings) {
    if(isset($db_settings['socket']) && strlen($db_settings['socket'])>2) {
			$dsn="{$db_settings['dbtype']}:unix_socket={$db_settings['socket']};dbname={$db_settings['database']}"; 
		} else {
			$dsn="{$db_settings['dbtype']}:host={$db_settings['host']};port={$db_settings['port']};dbname={$db_settings['database']}";
		}
    $pdo = new PDO( $dsn, $db_settings['username'] , $db_settings['password'] );
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE);
		$pdo->exec("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
		return $pdo;
  }

  

  
  public function insert(WaxModel $model) {
    $stmt = $this->exec($this->prepare($this->insert_sql($model)), $model->row);
    $model->row[$model->primary_key]=$this->db->lastInsertId();
    return $model;
	}
  
  public function update(WaxModel $model) {
    $this->exec($this->prepare($this->update_sql($model)), $model->row);
    $id = $model->primval;
    return $model;
  }
  
  public function delete(WaxModel $model) {
    $sql = $this->delete_sql($model);
    if(!$model->primval()) {
      $filters = $this->filter_sql($model);
      if($filters["sql"]) $sql.= " WHERE ";
      $sql.=$filters["sql"];
      $params =$filters["params"];
      $sql.= $this->order($model);
      $sql.= $this->limit($model);
    }
    return $this->exec($this->db->prepare($sql), $params);
  }
  
  public function select(WaxModel $model) {
    $params = array();
    if($model->sql) {
      $sql = $model->sql;
    } else {
      $sql .=$this->select_sql($model);
			
			$join = $this->left_join($model);
			$sql .=$join["sql"];
			$params = $join["params"];
			
      $filters = $this->filter_sql($model);
      if($filters["sql"]) $sql.= " WHERE ";
      $sql.=$filters["sql"];
      if($params) $params = array_merge($params, $filters["params"]);
      else $params = $filters["params"];
      
			//add filters from the other side of the join
			if($model->is_left_joined && $model->left_join_target instanceof WaxModel){
        $join_filters = $this->filter_sql($model->left_join_target);
        if($join_filters["sql"])
          if(strpos($sql,"WHERE") === false) $sql.= " WHERE ";
          else $sql.= " AND ";
        $sql.=$join_filters["sql"];
        if($params) $params = array_merge($params, $join_filters["params"]);
        else $params = $join_filters["params"];
      }
			
      $sql.= $this->group($model);
      $sql.= $this->having($model);
      $sql.= $this->order($model);
      $this->sql_without_limit = $sql;
      $sql.= $this->limit($model);
    }
    $stmt = $this->prepare($sql);
    $this->exec($stmt, $params);
    $this->row_count_query($model);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  
  public function prepare($sql) {
    try { $stmt = $this->db->prepare($sql); } 
    catch(PDOException $e) {
		  $err = $e->getMessage();
			throw new WaxSqlException( "{$err}", "Error Preparing Database Query", $sql );
      exit;
		}
		return $stmt;
  }
  
  public function exec($pdo_statement, $bindings = array(), $swallow_errors=false) {
    try {
      WaxLog::log("info", "[DB] ".$pdo_statement->queryString);
      if(count($bindings)) WaxLog::log("info", "[DB] Values:".join($bindings,",") );
			$pdo_statement->execute($bindings);
		} catch(PDOException $e) {
			$err = $pdo_statement->errorInfo();
			WaxLog::log("error", "[DB] ". $err[2]);
      if(!$swallow_errors) throw new WaxSqlException( "{$err[2]}", "Error Executing Database Query", $pdo_statement->queryString."\n".print_r($bindings,1) );
		}
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
  
  
  public function random() {
    return "RAND()";
  }
  
  
  /**
   * Query Specific methods, construct driver specific language
   */
	public function insert_sql($model) {
	  return "INSERT into `{$model->table}` (`".join("`,`", array_keys($model->row))."`) 
             VALUES (".join(",", array_keys($this->bindings($model->row))).")";
	}

  public function update_sql($model) {
    return "UPDATE `{$model->table}` SET ".$this->update_values($model->row).
      " WHERE `{$model->table}`.{$model->primary_key} = {$model->row[$model->primary_key]}";
  }
   
  public function select_sql($model) {
    $sql .= "SELECT ";
    if(is_array($model->select_columns) && count($model->select_columns)) $sql.= join(",", $model->select_columns);
    elseif(is_string($model->select_columns)) $sql.=$model->select_columns;
		//mysql extra - if limit then record the number of rows found without limits
		elseif($model->is_paginated) $sql .= "SQL_CALC_FOUND_ROWS *";
    else $sql.= "*";
    $sql.= " FROM `{$model->table}`";
    return $sql;
  }
  
  public function delete_sql($model) {
    $sql = "DELETE FROM `{$model->table}`";
    if($model->primval()) $sql .= " WHERE {$model->primary_key}={$model->primval()}";
    return $sql;
  }
  
  public function row_count_query($model) {
    if($model->is_paginated) {
      $extrastmt = $this->db->prepare("SELECT FOUND_ROWS()");
		  $this->exec($extrastmt);
		  $found = $extrastmt->fetchAll(PDO::FETCH_ASSOC);
		  $this->total_without_limits = $found[0]['FOUND_ROWS()'];
	  }
  }
  
  public function left_join($model) {
		if($model->is_left_joined && count($model->join_conditions)){
		  $conditions = $this->filter_sql($model,"join_conditions");
		  if($model->left_join_target instanceof WaxModel) $join_table = $model->left_join_target->table;
		  else $join_table = $model->left_join_target;
		  $sql = " LEFT JOIN `" . $join_table . "` ON ( " . $conditions["sql"] . " )";
		  return array("sql"=>$sql, "params"=>$conditions["params"]);
	  }
  }
  public function group($model) {if($model->group_by) return " GROUP BY {$model->group_by}"; }
  public function having($model) {if($model->having) return " HAVING {$model->having}";  }
  public function order($model) {if($model->order) return " ORDER BY {$model->order}";}
  public function limit($model) {if($model->limit) return " LIMIT {$model->offset}, {$model->limit}";}
  
  public function filter_sql($model, $filter_name = "filters") {
    $params = array();
    $sql = "";
    if(count($model->$filter_name)) {
      foreach($model->$filter_name as $filter) {
        if(is_array($filter)) {
          $sql.= "`$model->table`.".$filter["name"].$this->operators[$filter["operator"]].$this->map_operator_value($filter["operator"], $filter["value"]);
          if(is_array($filter["value"])) foreach($filter["value"] as $val) $params[]=$val;
          else $params[]=$filter["value"];
          $sql .= " AND ";
        } else {
          $sql.= $filter." AND ";
        }
      }
    }
    $sql = rtrim($sql, "AND ");
    return array("sql"=>$sql, "params"=>$params);
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
    $text = $this->db->quote($text);
    // Run the query adding the weighting supplied in the columns array
    $model->select_columns = "SQL_CALC_FOUND_ROWS * ,(";
    foreach($columns as $name=>$weighting) {
      $model->select_columns.="($weighting * (MATCH($name) AGAINST ($text)) ) +";
    }
    $model->select_columns = rtrim($model->select_columns, "+");
    $model->select_columns .= ") AS relevance ";
    $model->filter("MATCH(".implode(",", $cols).") AGAINST ($text IN BOOLEAN MODE)");
    $model->having = "relevance > 0";
    $model->order = "relevance DESC";
    // Add an arbitrary limit to force found_rows to run
    if(!$model->limit) $model->limit(1000);
    return $model;
  }
  
  
  
  
  
  /**
   * Introspection and structure creation methods
   *
   */
  
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
  
  
  
  

  /**
   * Internal helper methods
   *
   */

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
  
  protected function map_operator_value($operator, $value) {
    switch($operator) {
      case "=": return "?";
      case "!=": return "?";
      case "~": return "%?%";
      case "in": return "(".rtrim(str_repeat("?,", count($value)), ",").")";
    }
  }
  
  
  


} // END class 


