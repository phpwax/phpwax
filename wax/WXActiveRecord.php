<?php
require_once "WXValidations.php";

/*
 * @package wx.php.core
 *
 * This class is based in part on CBL ActiveRecord. 
 * For more information, see:
 *  http://31tools.com/cbl_activerecord/
 */

/**
 *  active record
 *  @package wx.php.core
 */
class WXActiveRecord extends WXValidations implements Iterator
{
	protected static $default_pdo = null;
	protected static $column_cache = null;
	protected $pdo = null;
  public $table = null;
  public $primary_key="id";
  protected $row = array();
  protected $constraints = array();
  protected $children = array();
	protected $columns = array();
	protected $has_many_throughs = array();
	public $paginate_page;
	public $paginate_limit;
	public $paginate_total;

 /**
  *  constructor
  *  @param  mixed   param   PDO instance,
  *                          or record id (if integer),
  *                          or constraints (if array) but param['pdo'] is PDO instance
  */
	function __construct($param=null) {
		$this->pdo = self::$default_pdo;
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
				$this->_find( $param );
				break;
			case strtolower( get_class( $param ) ) == 'pdo':
				$this->pdo = $param;
			default:
				break;
			
		}
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
  	return self::$default_pdo;
  }

    /**
     * get PDO instance
     */
	function getPDO() {
  	return $this->pdo;
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
    	return $this->row[$name];
    }
	  
  /**
   *    Then we see if the attribute has a dedicated method
   */ 
   if(method_exists($this, $name)) {
     return $this->{$name}();
   } 

	 /**
    *  Next we try and link to a child object of the same name
	  */
    $id = $this->row[$this->primary_key];
    $class_name = WXInflections::camelize($name, true);
    if($id) {
    	$foreign_key = $this->table . '_id';
			if(array_key_exists( $name, $this->children ) && $this->children[$name]->getConstraint( $foreign_key ) == $id ) {
      	// return cached instance
        return $this->children[$name];
      }
      if(class_exists($class_name)) {
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
  	if( ! is_array( $this->row ) ) {
    	$this->row = array();
    }
    $this->row[$name] = $value;
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
		$list = $this->find_all($params);
		$list = array_values($list);
		return $list[0];
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
			return $sth->fetchAll( PDO::FETCH_ASSOC );
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
  
  public function has_many($join, $through, $on) {
    $this->has_many_throughs[]=array($join, $through, $on);
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
		
		$sql = $this->build_query($params);
		try {
		  $row_list = $this->query($sql, "all");
	  } catch(PDOException $e) {
	    $error = $e->errorInfo[2];
      throw new WXActiveRecordException( $error, "Error Preparing Database Query" );
    }
		$item_list = array();
		foreach( $row_list as $row ) {
			$newtable=$this->camelize($this->table);
     	$item = new $newtable( $this->pdo );
			$item->row = $row;
			$item->constraints = $this->constraints;
			if (isset($row['id'])) {
				$item_list[$row['id']] = $item;				
			} else {
				$item_list[] = $item;
			}
    }		
    return array_values($item_list);
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
    if( ! $sth->execute( ) ) {
			$err = $sth->errorInfo();
      throw new WXActiveRecordException( "{$err[2]}:{$sql}", "Error Preparing Database Query" );
    }
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
		  $this->$k=$v;
		}
	  return $this->save();
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
		if(is_array($_POST[WXInflections::underscore(get_class($this))])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function handle_post($attributes=null) {
	  if($this->is_posted()) {
	    if(!$attributes) $attributes = $_POST[WXInflections::underscore(get_class($this))];
	    $atts = print_r($attributes, 1); error_log($atts);
	    return $this->update_attributes($attributes);
	  }
	  return false;
	}
	
	public function paginate($per_page, $options=array(), $parameter="page") {
    $_GET[$parameter] ? $this->paginate_page = $_GET[$parameter] : $this->paginate_page = 1;
    $this->paginate_limit = $per_page;
    $this->paginate_total = $this->count($options);
    $offset = (($this->paginate_page-1) * $per_page);
    $options = array_merge($options, array("limit"=>$per_page, "offset"=>$offset));
    return $this->find_all($options);
  }
	
	public function __call( $func, $args ) {
	  $func = WXInflections::underscore($func);
	  $finder = explode("by", $func);
		$what=explode("and", $finder[1]);
		foreach($what as $key=>$val) {
		  $what[$key]=rtrim(ltrim($val, "_"), "_");
		}
		
    if( $args ) {
			if(count($what)==2) { 
				$conds=$what[0]."='".$args[0]."' AND ".$what[1]."='".$args[1]."'";
			}else{
				$conds=$what[0]."='".$args[0]."'";
			}
			if(is_array($args[1]) && isset($args[1]["conditions"])) $conds.=" AND ".$args[1]["conditions"];
			  elseif(is_array($args[2]) && isset($args[2]["conditions"])) $conds.=" AND ".$args[2]["conditions"];
			$params = array("conditions"=>$conds);
			if($finder[0]=="find_all_") {
        return $this->find_all($params);
      } else {
        return $this->find_first($params);
      }
    }
  }
  
  /**
  	*  These are left deliberately empty in the base class
  	*  
  	*/	

  	public function before_save() {}
  	public function after_save() {}
  	public function before_update() {}
  	public function after_update() {}
  	public function before_insert() {}
  	public function after_insert() {}
  	public function before_delete() {}
  	public function after_delete() {}

}

?>
