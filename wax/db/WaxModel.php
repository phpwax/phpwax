<?php

/**
 * Base Database Class
 *
 * @package PHP-Wax
 * @author Ross Riley
 * 
 * Allows models to be mapped to application objects
 **/
class WaxModel {
  
  static public $adapter;
  static public $db_settings;
  public $db = false;
  public $table = false;
  public $primary_key="id";
  public $primary_type = "AutoField";
  public $primary_options = array();
  public $row = array();
  public $columns = array();
  public $select_columns = array();
  public $filters = array();
	public $group_by = false;
	public $having = false;
  public $order = false;
  public $limit = false;
  public $offset = "0";
  public $sql = false;
  public $errors = array();
  public $persistent = true;
  public $identifier = false;
  static public $object_cache = array();

	
  /**
   *  constructor
   *  @param  mixed   param   PDO instance,
   *                          or record id (if integer),
   *                          or constraints (if array) but param['pdo'] is PDO instance
   */
 	function __construct($params=null) {
 		if(!$this->db = new self::$adapter(self::$db_settings)) {
    	throw new WaxDbException("Cannot Initialise DB", "Database Configuration Error");
    }
 		$class_name =  get_class($this) ;
 		if( $class_name != 'WaxModel' && !$this->table ) {
 			$this->table = Inflections::underscore( $class_name );
 		}
 		if($params && is_numeric($params)) {
 		  $res = $this->filter(array($this->primary_key => $params))->first();
 		  $this->row=$res->row;
 		  $this->clear();
 		}
 		
 		$this->define($this->primary_key, $this->primary_type, $this->primary_options);
 		$this->setup();
 		$this->set_identifier();
 		// If a scope is passed into the constructor run a method called scope_[scope]().
 		if($params && is_string($params)) {
 		  $method = "scope_".$params;
	    if(method_exists($this, $method)) $this->$method;
	  }
 	}
 	
 	static public function load_adapter($db_settings) {
 	  $adapter = "Wax".ucfirst($db_settings["dbtype"])."Adapter";
 	  self::$adapter = $adapter;
 	  self::$db_settings = $db_settings;
 	}
 	
 	public function define($column, $type, $options=array()) {
 	  $this->columns[$column]=array($type, $options);
 	}
 	
 	public function add_error($field, $message) {
 	  $this->errors[$field][]=$message;
 	}
 	
 	public function filter($filters) {
 	  if(is_string($filters)) $this->filters[]=$filters;
 	  else {
      foreach((array)$filters as $key=>$filter) {
        if(is_array($filter)) $this->filters[]= $key." IN(".join(",",$filter).")";
        else $this->filters[]= $key."=".$this->db->quote($filter);
      }
    }
    return $this;
 	}
 	
 	
 	/**
 	 * Search Function, hands over to the DB to perform natural language searching.
 	 * Takes an array of columns which can each have a weighting value
 	 *
 	 * @param string $text 
 	 * @param array $columns 
 	 * @return WaxRecordset Object
 	 */
 	
 	public function search($text, $columns = array()) {
 	  $res = $this->db->search($this, $text, $columns);
    return $res;
 	}
 	
 	/**
 	 * Scope function... allows a named scope function to be called which configures a view of the model
 	 *
 	 * @param string $scope 
 	 * @return $this
 	 */
 	
 	public function scope($scope) {
 	  $method = "scope_".$scope;
    if(method_exists($this, $method)) $this->$method;
    return $this;
 	}
 	
 	
 	public function clear() {
    $this->filters = array();
    $this->order = false;
    $this->limit = false;
    $this->offset = "0";
    $this->sql = false;
    $this->errors = array();
    return $this;
 	}
 	
 	
 	public function validate() {
 	  foreach($this->columns as $column=>$setup) {
 	    $field = new $setup[0]($column, $this, $setup[1]);
 	    $field->validate();
 	    if($field->errors) {
 	      $this->errors[$column] = $field->errors;
      }
 	  }
 	  if(count($this->errors)) return false;
 	  return true;
 	}
 	
 	public function get_errors() {
 	  return $this->errors;
 	}

 	public function stealth_get_errors() {
 	  return $this->errors;
 	}

  public function get_col($name) {
    return new $this->columns[$name][0]($name, $this, $this->columns[$name][1]);
  }
  
  static public function get_cache($model, $field, $id) {
    $data = self::$object_cache[$model][$field][$id];
    if($data) {
      $row = new $model;
      $row->set_attributes($data);
      print_r($row); exit;
      return $row;
    }
    return false;
  }
  
  static public function set_cache($model, $field, $id, $value) {
    self::$object_cache[$model][$field][$id]=$value->row;
  }
  
  /**
   * output_val function
   * Gets the output value of a field,
   * Allows transformation of data to display to user
   * @param string $name 
   * @return mixed
   */
  
  public function output_val($name) {
    $field = $this->get_col($name);
    return $field->output();
  }
  
  public function set_identifier() {
    // Grab the first text field to display
    foreach($this->columns as $name=>$col) {
      if($col[0]=="CharField") {
        $label_field = $name;
      }
      if($label_field) {
        $this->identifier = $label_field;
        return true;
      }
    }
  }

     /**
      *  get property
      *  @param  string  name    property name
      *  @return mixed           property value
      */
 	public function __get($name) {
    if(array_key_exists($name, $this->columns)) {
      $field = $this->get_col($name);
      return $field->get();
    }
    elseif(method_exists($this, $name)) return $this->{$name}();
    elseif(array_key_exists($name, $this->row)) return $this->row[$name];
  }


  /**
   *  set property
   *  @param  string  name    property name
   *  @param  mixed   value   property value
   */
 	public function __set( $name, $value ) {
    if(array_key_exists($name, $this->columns)) {
 	    $field = $this->get_col($name);
 	    $field->set($value);
    } else $this->row[$name]=$value;
  }
  
  /**
   *  __toString overload
   *  @return  primary key of class
   */
 	public function __toString() {
    return $this->{$this->primary_key};
  }



 /**
  *  Insert record to table, or update record data
  *  Note that this operation is only carried out if the model
  *  is configured to be persistent.
  */
 	public function save() {
 	  $this->before_save();
 	  foreach($this->columns as $col=>$setup) $this->get_col($col)->save();
 	  if(!$this->validate) return false;
 	  if($this->persistent) {
 	    if($this->primval) $res = $this->update();
 	    else $res = $this->insert();
 		}
 		$res->after_save();
 		return $res;
  }

    /**
     *  delete record from table
     *  @param  mixed id    record id
     *  @return boolean
     */
 	public function delete() {
 	  $this->before_delete();
		//before we delete this, check fields - clean up joins by delegating to field
		foreach($this->columns as $col=>$setup) $this->get_col($col)->delete();
 	  $res = $this->db->delete($this);
    $this->after_delete();
    return $res;
  }

 	public function order($order_by){
		$this->order = $order_by;
		return $this;
	}
	public function offset($offset){
		$this->offset = $offset;
		return $this;
	}
	public function limit($limit){
		$this->limit = $limit;
		return $this;
	}
	public function group($group_by){
		$this->group_by = $group_by;
		return $this;
	}
	public function sql($query) {
	  $this->sql = $query;
	  return $this;
	}
	
	//take the page number, number to show per page, return paginated record set..
	public function page($page_number="1", $per_page=10){
		return new WaxPaginatedRecordset($this, $page_number, $per_page);
	}
	
  public function update( $id_list = array() ) {
    $this->before_update();
    $res = $this->db->update($this);
    $res->after_update();
    return $res;
  }

  public function insert() {
    $this->before_insert();
    $res = $this->db->insert($this);
    $this->{$this->primary_key} = $res->primval;
    $res->after_insert();
    return $res;
  }
  
  public function syncdb() {
    $res = $this->db->syncdb($this);
    return $res;
  }
  
  public function query($query) {
    return $this->db->query($query);
  }
  
  /**
   * Create function
   *
   * @return WaxModel Object
   **/
  public function create($attributes = array()) {
 		$row = clone $this;
 		return $row->update_attributes($attributes);
  }

  /**
   * Select and return dataset
   * @return WaxRecordset Object
   */
 	public function all() {
 	  $res = $this->db->select($this);
 	  return new WaxRecordset($this, $res);
 	}
 	
 	/**
   * Select and return single row data
   * @return WaxModel Object
   */
 	public function first() {
 	  $this->limit = "1";
 	  $row = clone $this;
 	  $res = $this->db->select($row);
 	  if($res[0])
 	    $row->set_attributes($res[0]);
 	  else
 	    $row = false;
 	  return $row;
 	}


 	public function update_attributes($array) {
    foreach($array as $k=>$v) {
      WaxLog::log("info", "Setting $k as $v");
      $this->$k=$v;
		}
		return $this->save();
 	}
 	
 	public function set_attributes($array) {
		foreach((array)$array as $k=>$v) {
		  $this->$k=$v;
		}
	  return $this;
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
 	
 	/**
 	 * primval() function
 	 *
 	 * @return mixed
 	 * simple helper to return the value of the primary key
 	 **/
 	public function primval() {
    return $this->{$this->primary_key};
  }


  /**
   * Maintains Backward compatibility 
   *
   * @param array $options 
   * @return WaxRecordset
   */
  
  public function find_all($options=array()) {
		$this->clear();
    if($options["conditions"]) $this->filter($options["conditions"]);
    if($options["limit"]) $this->limit=$options["limit"];
    if($options["order"]) $this->order = $options["order"];
    if($options["page"] && $options["per_page"]) return $this->page($options["page"], $options["per_page"]);
    return $this->all();
  }

  /**
   * Maintains Backward compatibility 
   *
   * @param array $options 
   * @return WaxModel
   */
  
  public function find($options=array()) {
		$this->clear();
    if($options["conditions"]) $this->filter($options["conditions"]);
    if($options["limit"]) $this->limit=$options["limit"];
    if($options["order"]) $this->order = $options["order"];
    return $this->first();
  }
  
  public function find_by_sql($sql) {
    $this->sql($sql);
    $res = $this->db->select($this);
    return new WaxRecordset($this, $res); 	  
  }
  
  public function dynamic_finders($func, $args) {
		$func = WXInflections::underscore($func);
	  $finder = explode("by", $func);
		$what=explode("and", $finder[1]);
		foreach($what as $key=>$val) $what[$key]=rtrim(ltrim($val, "_"), "_");

    if( $args ) {
      if(count($what)==2) $this->filter(array($what[0]=>$args[0], $what[1], $args[1]));
			else $this->filter(array($what[0]=>$args[0]));

			if(is_array($args[1])) $params = $args[1];
			elseif(is_array($args[2])) $params = $args[2];
			
			if($finder[0]=="find_all_") return $this->find_all($params);
      else return $this->find($params);
    }
	}


 	public function __call( $func, $args ) {
 	  return $this->dynamic_finders($func, $args);
  }
   
  public function __clone() {
  	$this->setup();
   }

	public function total_without_limits(){
		return $this->db->total_without_limits;
	}
   /**
   	*  These are left deliberately empty in the base class
   	*  
   	*/	

 		public function setup() {}
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