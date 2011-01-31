<?php

/*
 * @package PHP-Wax
 *
 * This class is based in part on CBL ActiveRecord. 
 * For more information, see:
 *  http://31tools.com/cbl_activerecord/
 */

/**
 *  active record
 *  @package PHP-Wax
 */
class WXActiveRecord extends WXValidations implements Iterator
{
	protected static $default_pdo = null;
	public static $pdo_settings = null;
	protected static $column_cache = null;
	protected $pdo = null;
  public $table = null;
  public $primary_key="id";
  protected $row = array();
  protected $constraints = array();
  protected $children = array();
	protected $columns = array();
	public $has_many_throughs = array();
	public $paginate_page;
	public $paginate_limit;
	public $paginate_total;
	public $per_page;

 /**
  *  constructor
  *  @param  mixed   param   PDO instance,
  *                          or record id (if integer),
  *                          or constraints (if array) but param['pdo'] is PDO instance
  */
	function __construct($param=null) {
	  $db = self::$pdo_settings;
	  $this->pdo = self::getDefaultPDO();
      
		$class_name =  get_class($this) ;
		
		if( $class_name != 'WXActiveRecord' ) {
			$this->table = WXInflections::underscore( $class_name );
			if(!self::$column_cache[$this->table]) {
			  self::$column_cache[$this->table] = $this->column_info;
			}
			$this->columns = self::$column_cache[$this->table];
		}
		
		switch(true) {
			case is_numeric($param):			
			case is_string($param):
				$result = $this->_find( $param );
				break;
			case strtolower( get_class( $param ) ) == 'pdo':
				$this->pdo = $param;
			default:
				break;
			
		}
		$this->after_setup();
	}

 /**
  *  set default PDO instnace
  *  @param  object  pdo     PDO instance
  */
	static function setDefaultPDO( $pdo ) {
		return self::$default_pdo = $pdo;
  }

 /**
  *  get default PDO instance
  *  @return object      PDO instance
  */
	static function getDefaultPDO() {
	  if(!self::$default_pdo) {
	    $db = self::$pdo_settings;
		  if(isset($db['socket']) && strlen($db['socket'])>2) {
  			$dsn="{$db['dbtype']}:unix_socket={$db['socket']};dbname={$db['database']}"; 
  		} else {
  			$dsn="{$db['dbtype']}:host={$db['host']};port={$db['port']};dbname={$db['database']}";
  		}
		  try {
		    $pdo = new PDO( $dsn, $db['username'] , $db['password'] );
    		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		  } catch (Exception $e) {
		    throw new WaxDbException("Cannot Initialise DB", "Database Configuration Error", $db);
		  }
      self::$default_pdo = $pdo;
    }
    return self::$default_pdo;
  }

    /**
     * get PDO instance
     */
	function getPDO() {
  	return $this->pdo;
  }


	/**
	 * Join tables are automatically created, this little method handles it.
	 *
	 * @return void
	 * @param $join 
	 **/
	protected function initialise_has_many($join, $rel) {
		$migration = new WXMigrate(true);
		$migration->create_column($this->table."_id", "integer");
		$migration->create_column($rel."_id", "integer");
		$migration->create_column("order", "integer", 8, false, "0");
	 	$migration->create_table($join, false);
	}
 /**
   * has_many returns an array of associated objects. There is a recursion block in __get 
	 * which performing the operation statically overcomes.
	 * This is called from __get and shouldn't be used externally.
   */

	static function get_relation($class, $pdo, $foreign_key, $id) {
		$child = new $class($pdo);
		$child->setConstraint( $foreign_key, $id );
		return $child;
	}
	
	static function get_owner($class, $pdo, $id) {
	  $owner = new $class($pdo);
	  return $owner->find($id);
	}

    /**
     *  get property
     *  @param  string  name    property name
     *  @return mixed           property value
     */
	public function __get( $name ) {
	 /**
    *  First job is to return the value if it exists in the table
	  */
    if( array_key_exists( $name, $this->row ) ) {
    	return stripslashes($this->row[$name]);
    }
	  
   /**
    *    Then we see if the attribute has a dedicated method
    */ 
    if(method_exists($this, $name)) {
     	return $this->{$name}();
   	}

	 /**
    *  Then we see if it has been setup as a has_many relationship.
	  *  This is passed on to the has_many_methods method which will
	  *  perform an operation on the associated table based on the join table.
    */ 
    if(array_key_exists($name, $this->has_many_throughs)) {
			return $this->has_many_methods("get", $name);
   	}

	 /**
    *  Next we try and link to a child object of the same name
	  */
	  $link = $name."_id";
	  $id = $this->row[$this->primary_key];
    $class_name = WXInflections::camelize($name, true);
	  if($own = $this->row[$link]) {
	    if(class_exists($class_name, false)) {
				return WXActiveRecord::get_owner($class_name, $this->pdo, $own);
      }
	  }
    
    if($id) {
    	$foreign_key = $this->table . '_id';
      if(class_exists($class_name, false)) {
				return WXActiveRecord::get_relation($class_name, $this->pdo, $foreign_key, $id);
      } 
    } 

    return false;
  }


 /**
  *  set property
  *  @param  string  name    property name
  *  @param  mixed   value   property value
  */
	public function __set( $name, $value ) {
	
		// Nice shortcut to add an object to an association.
  	if(array_key_exists($name, $this->has_many_throughs)) {
			return $this->has_many_methods("add", $name, $value);
		}
		
  	if( ! is_array( $this->row ) ) {
    	$this->row = array();
    }
    if(!is_array($value)) $this->row[$name] = $value;
    else $this->row[$name] = $value;
    
  }

 /**
  *  set constraints
  *  @param  string  name    column name
  *  @param  mixed   value   column value
  */
	function setConstraint( $name, $value ) {
		$this->constraints[$name] = $value;
  }

 /**
  *  get constraints
  *  @param  string  name    column name
  *  @return mixed           column value
  */
	function getConstraint( $name ) {
  	return array_key_exists( $name, $this->constraints) ? $this->constraints[$name] : null;
  }

 /**
  *  get one record
  *  @param  mixed id            record id
  *  @return WXActiveRecord    this instance, or null if failed
  */
	public function find( $id = null, $params = null ) {
	  if(is_array($id)) return $this->array_of_ids($id);
  	$record = clone( $this );
    return $record->_find( $id, $params ) ? $record : null;
  }

	public function find_first($params=array()) {
	  $params = array_merge($params, array("limit"=>"1"));
		$sql = $this->build_query($params);
		$newtable=camelize($this->table);
   	$item = new $newtable( $this->pdo );
		$item->row = $this->query($sql, "one");
		if(!$item->row) return false;
		$item->constraints = $this->constraints;
		return $item;
	}
	
	function find_by_sql($sql) {
		return $this->find_all(array("sql"=>$sql));
	}
	
	public function query( $sql, $type="one" ) {
		try {
			$sth = $this->pdo->prepare( $sql );
			$binding_params = $this->_makeBindingParams( $this->constraints );
			if($binding_params) {
				$sth->execute($binding_params);
			}
		} catch(PDOException $e) {
			$err = $this->pdo->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
		}			
		if( ! $sth->execute( ) ) {
			$err = $sth->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
    }
		if($type=="all") {
		  return $sth;
		} else {		
			return $sth->fetch( PDO::FETCH_ASSOC );
		}
	}

 /**
  *  get one record helper
  *  @param  mixed id            record id
  *  @return boolean
  */
	protected function _find( $id = null, $params = null ) {
  	if( is_null( $params ) ) {
    	$params = array();
    }
		$params['find_id'] = $id;
		$sql = $this->build_query($params);
		$row = $this->query($sql, "one");
		if(!$row) {
			return false;
		}
		$this->row = $row;
 		return true;
  }
  
  public function array_of_ids($array) {
    $collection = array();
    $sql= "id IN(";
    foreach($array as $id) {
      $sql .= "$id,";
    }
    $sql = rtrim($sql, ",");
    $sql.=")";
    return $this->find_all(array("conditions"=>$sql));
    return $collection;
  }
  
  /**
   * Adds a has_many join to the object. 
   *
   * @return void
   * @param $table
   * @param $method
   * @param $join
   **/

  public function has_many($table, $method, $join_table=false) {
		if(!$join_table) {
			$join_elements=array($this->table, $table);
			sort($join_elements);
			$join_table = implode("_", $join_elements);
		}
    $this->has_many_throughs[$method]=array($table, $join_table);
		$this->initialise_has_many($join_table, $table);
  }

 /**
  *  get record list
  *  @param  array   params  option array
  *                          params['conditions'] : WHERE phrase in SQL
  *                          params['order'] : ORDER phrase in SQL
  *  @return array           array of ActiveRecord Objects
  */
	function find_all( $params = null, $join = null ) {
		if (! is_array($params)) $params = array();
		if (! is_array($join)) $join = array();
		$params['join'] = $join;
		if($params["page"]) return $this->build_paginated($params);
		$sql = $this->build_query($params);
		try {
		  $row_list = $this->query($sql, "all");
	  } catch(PDOException $e) {
	    $error = $e->errorInfo[2];
      throw new WXActiveRecordException( $error, "Error Preparing Database Query" );
    }
    
    $obj = new WXRecordset($row_list->fetchAll(PDO::FETCH_ASSOC), $this->pdo, WXInflections::camelize($this->table), $this->constraints);
    return $obj->classic_rowset();
  }
  
  protected function build_paginated($params) {
    $count_params=$params;
    $count_params["columns"]="COUNT(*) as count";
    if($params["per_page"]) $per_page = $params["per_page"];
    elseif($this->per_page) $per_page = $this->per_page;
    else $per_page = 10;
    $params["offset"] = ($params["page"] - 1) * $per_page;;
    $params["limit"]=$per_page;
    $sql = $this->build_query($params);
		try {
		  $row_list = $this->query($sql, "all");
	  } catch(PDOException $e) {
	    $error = $e->errorInfo[2];
      throw new WXActiveRecordException( $error, "Error Preparing Database Query" );
    }    
    $page = new WXPaginatedRecordset($row_list->fetchAll(PDO::FETCH_ASSOC), $this->pdo, $this->camelize($this->table), $this->constraints);
    $sql = $this->build_query($count_params);
    $page->current_page = $count_params["page"];
    $page->per_page = $count_params["per_page"];
    $count = $this->query($sql, "one");
    $page->per_page = $per_page;
    $page->set_count($count["count"]);
    return $page;
  }


    /**
     *  insert record to table, or update record data
     */
	public function save() {
		$this->validations();
		if(!$this->validate()) {
			return false;
		}
		$this->before_save();
  	if( $this->row['id'] ) {
    	$i = $this->update();
    }else{
    	unset( $this->row['id'] );
      $i = $this->insert();
    }
		$this->after_save();
		return $i;
  }

   /**
    *  delete record from table
    *  @param  mixed id    record id
    *  @return boolean
    */
	public function delete( $id ) {
	  $this->row['id']=$id;
	  $this->before_delete();
  	if( is_numeric( $id ) && ! isset( $this->has_string_id ) ) {
    	$id = intval( $id );
    }
    $this->constraints['id'] = $id;
    $sql = "DELETE FROM `{$this->table}` WHERE " . $this->_makeANDConstraints($this->constraints).';';
    $binding_params = $this->_makeBindingParams( $this->constraints );
    $sth = $this->pdo->prepare($sql);
    
    if( ! $sth->execute( $binding_params ) ) {
      $err = $sth->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
    }
    $this->row = array();
    $this->after_delete();
    return $sth->rowCount() > 0;
  }

  function count($params = null) {
    $sql = "SELECT COUNT(*) FROM `{$this->table}`";
    if (isset($params['conditions']) && $params['conditions'] != '') {
        $sql .= " WHERE {$params['conditions']}";
    }
    $sql .= ';';
    $sth = $this->pdo->query( $sql );
    return intval( $sth->fetchColumn() );
  }

  function update( $id_list = array() ) {
    $this->before_update();
		$this->clear_unwanted_values();
    $values = $this->row;
    unset($values['id']);
    
    $sql = "UPDATE `{$this->table}` SET ".$this->_makeUPDATEValues($values);
    if (isset($this->row['id']) && $this->row['id']) {
      $sql .= " WHERE `{$this->table}`.id=:id;";
    } else if (count($id_list)) {
      $sql .= ' WHERE '.$this->_makeIDList($id_list).';';
    } else {
      $err = $sth->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "No primary key(id) specified" );
    }
    $binding_params = $this->_makeBindingParams($this->row);
    
    $sth = $this->pdo->prepare($sql);
    if (! $sth) {
      $err = $sth->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
    }
    if (! $sth->execute($binding_params)) {
      $err = $sth->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
    }
    $this->after_update();
    return true;
  }

  function insert() {
    $this->before_insert();
		$this->clear_unwanted_values();
		$this->row = array_merge( $this->constraints, $this->row );
    $binding_params = $this->_makeBindingParams( $this->row );
    $sql = "INSERT INTO `{$this->table}` (" .
        implode( ', ', array_keys($this->row) ) . ') VALUES(' .
        implode( ', ', array_keys($binding_params) ) . ');';
    
    $sth = $this->pdo->prepare( $sql );
    if( ! $sth ) {
      $err = $sth->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
    }
    if( ! $sth->execute( $binding_params )) {
      $err = $sth->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
    }
    
    if( ! $this->row['id'] && ! isset( $this->has_string_id ) ) {
      $this->row['id'] = $this->pdo->lastInsertId();
			$this->after_insert();
      return intval( $this->row['id'] );
    }
    $this->after_insert();
    return $this->row['id'];
  }

  function uniqid($len = 8, $set = TRUE) {
    if ($len < 8) {
      throw new WXActiveRecordException( "Database Error", "ID length is short." );
    }
    $sql = "SELECT id FROM `{$this->table}` WHERE id=:id;";
    $sth = $this->pdo->prepare($sql);
    do {
      $id = substr(md5(uniqid()), 0, $len);
      $sth->execute(array('id'=>$id));
      $row = $sth->fetch();
      $sth->closeCursor();
    } while ($row);
      if ($set) {
        $this->id = $id;
      }
    return $id;
  }

	function clear_unwanted_values() {
		foreach($this->row as $key=>$value) {
			if(!array_key_exists($key, $this->columns)) unset($this->row[$key]);
		}
	}

  function _makeANDConstraints( $array ) {
    foreach( $array as $key=>$value ) {
      if(is_null( $value ) ) {
        $expressions[] = "`{$this->table}`.{$key} IS NULL";
      } else {
        $expressions[] = "`{$this->table}`.{$key}=:{$key}";
      }
    }
    return implode( ' AND ', $expressions );
  }

  function _makeUPDATEValues( $array ) {
    foreach( $array as $key=>$value ) {
      $expressions[] ="`{$key}`=:{$key}";
    }
    return implode( ', ', $expressions );
  }

  function _makeBindingParams( $array ) {
		$params = array();
		foreach( $array as $key=>$value ) {
			$params[":{$key}"] = $value;
		}
    return $params;
  }

	private function build_query($params) {
		if( $params['distinct'] ) {
			$sql = "SELECT DISTINCT {$params['distinct']} FROM `{$this->table}`";
		} elseif( $params['columns'] ) {
    	$sql = "SELECT {$params['columns']} FROM `{$this->table}`";
    } else {
      $sql = "SELECT * FROM `{$this->table}`";
    }
    if($params['table']) $sql.=", ".$params['table'];
    
    if(!empty($params['join'])) {
      $join = $params['join'];
      if (count($join) && $join['table'] && $join['lhs'] && $join['rhs']) {
    	  $sql .= " INNER JOIN `{$join['table']}`".
      	  			" ON `{$this->table}`.{$join['lhs']}=`{$join['table']}`.{$join['rhs']}";
      }
    }
    $where = false;
    if( count( $this->constraints ) ) {
    	$sql .= ' WHERE ' . $this->_makeANDConstraints( $this->constraints );
      $where = true;
    }

    if(!$params['find_id']) {
  		if($params['conditions']) {
      	if( $where ) {
        	$sql .= " AND ({$params['conditions']})";
        } else {
          $sql .= " WHERE {$params['conditions']}";
          $where = true;
        }
      }
      if($params['group']) {
      	$sql .= " GROUP BY {$params['group']}";
      }
  		if($params['order']) {
      	$sql .= " ORDER BY {$params['order']}";
      }
      
  			
  		if( $params['direction'] ) {
      	$sql .= " {$params['direction']}";
      }
  
      if($params['limit']) {
      	$limit = intval( $params['limit'] );		
      	if($params['offset']) {
        	$offset = intval( $params['offset'] );
        	$sql .= " LIMIT {$offset}, {$limit} ";
        } else {
          $sql .= " LIMIT {$limit}";
        }
  		}
		} else {
			if( $where ) {
      	$sql .= " AND (`id`={$params['find_id']})";
      } else {
        $sql .= " WHERE `id`={$params['find_id']}";
        $where = true;
      }
		}
		
		if( $params["sql"]) {
			$sql=$params["sql"];
		}
		$sql .= ';';
		return $sql;
	} 

  function _makeIDList( $array ) {
  	$expressions = array();
    foreach ($array as $id) {
      $expressions[] = "`{$this->table}`.id=".
      $this->pdo->quote($id, isset($this->has_string_id) ? PDO_PARAM_INT : PDO_PARAM_STR);
    }
    return '('.implode(' OR ', $expressions).')';
  }

	/**
  * Convert underscore_words to camelCaps.
  */
  public function camelize($name){
    // lowercase all, underscores to spaces, and prefix with underscore.
    // (the prefix is to keep the first letter from getting uppercased
    // in the next statement.)
    $name = '_' . str_replace('_', ' ', strtolower($name));

    // uppercase words, collapse spaces, and drop initial underscore
    return ltrim(str_replace(' ', '', ucwords($name)), '_');
  }


  /**
    * Convert camelCaps to underscore_words.
    */
  public function underscore($name) {
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $name));
  }

	public function update_attributes($array) {
	  $this->clear_errors();
		foreach($array as $k=>$v) {
		  if(array_key_exists($k, $this->has_many_throughs)) {
  			$assoc[]=array($k, $v);
     	} else $this->$k=$v;
		}
		if(is_array($assoc) && count($assoc >0)) {
		  $res = $this->save();
		  foreach($assoc as $val) {
		    $this->$val[0]=$val[1];
		  }
		  return $res;
		} else return $this->save();
	}
	
	public function set_attributes($array) {
		if(!is_array($array)) return false;
		foreach($array as $k=>$v) {
		  $this->$k=$v;
		}
	  return true;
	}
		
	public function describe() {
    return $this->query("DESCRIBE `{$this->table}`", "all");
	}
	
	public function column_info() {
		$columns = array();
		foreach($this->describe() as $column) {
			$columns[$column["Field"]]=array($column["Type"], $column["Null"], $column["Default"]);
		}
		return $columns;
	}
	
	public function describe_field($field) {
		return $this->find_by_sql("DESCRIBE `{$this->table}` {$field}");
	}
		
  /**
   * iterator function current
   *
   * @return void
   **/
  public function current() {
    return current($this->row);
  }
  
  /**
   * iterator function key
   *
   * @return void
   **/
  public function key() {
    return key($this->row);
  }
  
  /**
   * iterator function next
   *
   * @return void
   **/
  public function next() {
    return next($this->row);
  }
	
	/**
   * iterator function rewind
   *
   * @return void
   **/
  public function rewind() {
    reset($this->row);
  }
  
  /**
   * iterator function valid
   *
   * @return void
   **/
  public function valid() {
    return $this->current() !== false;
  }
	
	public function is_posted() {
		if(is_array($_POST[$this->table])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function handle_post($attributes=null) {
	  if($this->is_posted()) {
	    if(!$attributes) $attributes = $_POST[$this->table];
	    return $this->update_attributes($attributes);
	  }
	  return false;
	}
	
	public function paginate($per_page, $options=array(), $parameter="page") {
    $_GET[$parameter] ? $page_number = $_GET[$parameter] : $page_number = 1;
    return $this->pagination($per_page, $options, $page_number);
  }
	//new version of paginate - page number passed in (probably via the route_array)
	public function pagination($per_page, $options=array(), $current_page="1"){
		if(empty($current_page)) $current_page=1;
		$this->paginate_page = $current_page;
		$this->paginate_limit = $per_page;
	  $this->paginate_total = $this->count($options);
	  $offset = (($this->paginate_page-1) * $per_page);		
	  $options = array_merge($options, array("limit"=>$per_page, "offset"=>$offset));
	  return $this->find_all($options);
	}

	public function dynamic_finders($func, $args) {
		$func = WXInflections::underscore($func);
	  $finder = explode("by", $func);
		$what=explode("and", $finder[1]);
		foreach($what as $key=>$val) {
		  $what[$key]=rtrim(ltrim($val, "_"), "_");
		}
    if( $args ) {
      if(count($what)==2) { 
				$conds=$what[0].'='.$this->pdo->quote($args[0]).' AND '.$what[1].'='.$this->pdo->quote($args[1]);
			}else{
				$conds=$what[0].'='.$this->pdo->quote($args[0]);
			}
			if(is_array($args[1])) {
				if(isset($args[1]["conditions"])) $conds.=" AND ".$args[1]["conditions"]; 
				$params = array_merge($args[1], array("conditions"=>$conds));
			} elseif(is_array($args[2])) {
				if(isset($args[2]["conditions"])) $conds.=" AND ".$args[2]["conditions"]; 
				$params = array_merge($args[2], array("conditions"=>$conds));
			} else $params = array("conditions"=>$conds);
			
			if($finder[0]=="find_all_") {
        return $this->find_all($params);
      } else {
        return $this->find_first($params);
      }
    }
	}
	
	public function has_many_methods($operation, $column, $value=null, $order="0") {
		if(is_array($value)) {
			if(isset($value[1])) $order = $value[1];
			$value = $value[0];
		}
		$current = $this->row[$this->primary_key];
		$rel = $this->has_many_throughs[$column][0];
		$join = $this->has_many_throughs[$column][1];
		switch($operation) {
		  case "findin":
			 	if(is_array($order)) $params = $order;
			 	$params['distinct']="{$this->table}.*";
			 	$params['table']="`$join`, `{$rel}`";
			 	if($params['conditions']) $params['conditions'].=" AND $join.{$rel}_id = '$value' AND $join.{$this->table}_id = {$this->table}.id";
			 	else $params['conditions']= "$join.{$rel}_id = '$value' AND $join.{$this->table}_id = {$this->table}.id";
				$result = $this->find_all($params);
				return $result;
			case "delete":
				return $this->pdo->query("DELETE FROM $join WHERE {$this->table}_id =$current and {$rel}_id = $value");
			 	break;
			case "add":
				$this->pdo->query("DELETE FROM $join WHERE {$this->table}_id =$current AND {$rel}_id = $value");
				return $this->pdo->query("INSERT INTO $join ({$this->table}_id, {$rel}_id, `order`) VALUES($current, $value, $order)");
				break;
			case "clear":
				return $this->pdo->query("DELETE FROM $join WHERE {$this->table}_id =$current");
				break;
			case "get":
				$rel_class = WXInflections::camelize($rel, true);
			 	$table = new $rel_class;
			 	if($current) {
				  return $table->find_by_sql("SELECT * FROM {$rel} RIGHT JOIN {$join} ON $join.{$rel}_id = {$rel}.id 
				    WHERE $join.{$this->table}_id = $current ORDER BY `order` ASC");
				} else return false;
			case "order":
				return $this->pdo->query("UPDATE $join SET `order`=$order WHERE {$this->table}_id = $current AND $value {$rel}_id = $value");
		}
	}
	
	public function __call( $func, $args ) {
	  $function = explode("_", $func);
		if(array_key_exists($function[1], $this->has_many_throughs) && count($function)==2) {
			return $this->has_many_methods($function[0], $function[1], $args);
		} else return $this->dynamic_finders($func, $args);
  }
  
  /**
  	*  These are left deliberately empty in the base class
  	*  
  	*/	

		public function after_setup() {}
  	public function before_save() {}
  	public function after_save() {}
  	public function before_update() {}
  	public function after_update() {}
  	public function before_insert() {}
  	public function after_insert() {}
  	public function before_delete() {}
  	public function after_delete() {}

}

class WXRecordset implements Iterator, ArrayAccess, Countable {

  protected $statement = false;
  public $rowset = false;
  protected $obj = false;
  protected $key = 0;
  protected $constraints = array();
  
  public function __construct($rowset, $pdo, $object, $constraints=array()) {
    $this->rowset = $rowset;
    $this->pdo = $pdo;
    $this->obj = $object;
    $this->constraints = $constraints;
  }
  
  public function next() {
    $this->key++;
  }
  
  public function current() {
    return $this->offsetGet($this->key);
  }
  
  public function key() {
    return $this->key;
  }
  
  public function rewind() {
    $this->key=0;
  }
  
  public function valid() {
    if($this->rowset[$this->key]) return true;
    return false;
  }
  
  public function offsetExists($offset) {
    if(count($this->rowset)>=$offset) return true;
    return false;
  }
  
  public function offsetGet($offset) {
    $obj = new $this->obj($this->pdo);
    $obj->set_attributes($this->rowset[$offset]);
    foreach($this->constraints as $k=>$v) $obj->setConstraint($k, $v);
    return $obj;
  }
  
  public function offsetSet($offset, $value) {
    $this->rowset[$offset]=$value;
  }
  
  public function offsetUnset($offset) {
    array_splice($this->rowset, $offset,1);
  }
  
  public function count() {return count($this->rowset);}
  
  public function classic_rowset() {
    $rows=array();
    foreach($this->rowset as $row) {
      $obj = new $this->obj($this->pdo);
      $obj->set_attributes($row);
      foreach($this->constraints as $k=>$v) $obj->setConstraint($k, $v);
      $rows[]=$obj;
    }
    return $rows;
  }
  
}

class WXPaginatedRecordset extends WXRecordset {
  
  public $current_page=1;
  public $total_pages=false;
  public $per_page=false;
  public $count=false;
  public function set_count($count) {
    $this->count = $count;
    $this->total_pages = ceil($count / $this->per_page);
  }
  
  public function next_page() { return $this->current_page +1;}
  public function previous_page() { return $this->current_page -1;}
  
  public function is_last($page) {
    if($page==$this->total_pages) return true;
    return false;
  }
  public function is_first() {
    if($this->current_page==1) return true;
    return false;
  }
  public function is_current($page) {
    if($this->current_page==$page) return true;
    return false;
  }
}


?>