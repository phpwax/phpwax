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
  
  static public $adapter = false;
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
	public $is_paginated = false;
	//joins
	public $is_left_joined = false;
	public $left_join_target = false;
	public $left_join_table_name = false;
	public $join_conditions = false;
  /**
   *  constructor
   *  @param  mixed   param   PDO instance,
   *                          or record id (if integer),
   *                          or constraints (if array) but param['pdo'] is PDO instance
   */
 	function __construct($params=null) {
 		if(self::$adapter && !$this->db = new self::$adapter(self::$db_settings)) {
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
 	  if($db_settings["dbtype"]=="none") return true;
 	  $adapter = "Wax".ucfirst($db_settings["dbtype"])."Adapter";
 	  self::$adapter = $adapter;
 	  self::$db_settings = $db_settings;
 	}
 	
 	public function define($column, $type, $options=array()) {
 	  $this->columns[$column]=array($type, $options);
 	}
 	
 	public function add_error($field, $message) {
 	  if(!in_array($message, (array)$this->errors[$field])) $this->errors[$field][]=$message;
 	}
 	
 	public function filter($filters, $params=false, $operator="=") {
 	  if(is_string($filters)) {
 	    if($params !== false) {
 	      $this->filters[] = array("name"=>$filters, "operator"=>$operator, "value"=>$params);
 	    } else $this->filters[]=$filters;
    }else {
      foreach((array)$filters as $key=>$filter) {
        if(is_array($filter)) {
          if(!strpos($key, "?")) {
            $this->filters[]= array( "name"=>$key, "operator"=>"in", "value"=>$filter);
          }
          else $this->filters[] = array("name"=>$key, "operator"=>"raw", "value"=>$filter);
        }
        else $this->filters[$key]= array("name"=>$key,"operator"=>"=", "value"=>$filter);
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
		$this->is_paginated = false;
		$this->is_left_joined = false;		
    $this->errors = array();
		$this->having = false;
		$this->select_columns = array();		
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
    if(!$this->columns[$name][0]) throw new WXException("Error", $name." is not a valid call");
    return new $this->columns[$name][0]($name, $this, $this->columns[$name][1]);
  }
  
  static public function get_cache($model, $field, $id, $transform = true) {
    $data = self::$object_cache[$model][$field][$id];
    if(!$transform) return $data;
    if($data) {
      //find target model to reinstantiate using the field name
      $model_this = new $model;
      if(is_array($data[0])) {
     	  return new WaxRecordset(new $model, $data);
      }else{
        $row = new $model;
        $row->set_attributes($data);
        return $row;
      }
    }
    return false;
  }
  
  static public function set_cache($model, $field, $id, $value) {
    if($value instanceof WaxModel)
      self::$object_cache[$model][$field][$id]=$value->row;
    elseif($value instanceof WaxRecordSet)
      self::$object_cache[$model][$field][$id]=$value->rowset;
    else
      self::$object_cache[$model][$field][$id]=$value;
  }
  
	static public function unset_cache($model, $field, $id = false){
		if(!$id) unset(self::$object_cache[$model][$field]);
		else unset(self::$object_cache[$model][$field][$id]);
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
    if($this->identifier) return true;
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
    elseif(is_array($this->row) && array_key_exists($name, $this->row)) return $this->row[$name];
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
 	  $associations = array();
 	  foreach($this->columns as $col=>$setup) {
 	    $field = $this->get_col($col);
 	    if(!$field->is_association) $this->get_col($col)->save();
 	    else $associations[]=$field;
 	  }
 	  if(!$this->validate) return false;
 	  if($this->persistent) {
 	    if($this->primval) $res = $this->update();
 	    else $res = $this->insert();
 		}
 		foreach($associations as $assoc) $assoc->save();
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
	
	public function random($limit) {
	  $this->order($this->db->random());
	  $this->limit($limit);
	  return $this;
	}
	
	public function dates($start, $end) {
	  
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
		$this->is_paginated = true;
		return new WaxPaginatedRecordset($this, $page_number, $per_page);
	}
	/**
	 * the left join function activates the flag to let the db adapter know a join will be used
	 * also takes the table to join to - returns $this so its chainable
	 * @param string $target (this can be a model name or the wax model itself)
	 * @return WaxModel $this
	 * @author charles marshall
	 */	
	public function left_join($target){
		if(is_string($target) || $target instanceof WaxModel){
		  $this->left_join_table_name = $target->table;
		  $this->left_join_target = $target;
  		$this->is_left_joined = true;
		}
		return $this;
	}
	/**
	 * takes the conditions to add to the join syntax in the db adapter 
	 * @param string $conditions or array $conditions 
	 * @return WaxModel $this 
	 */	
	public function join_condition($conditions){
 	  if(is_string($conditions)) $this->join_conditions[]=$conditions;
 	  else {
      foreach((array)$conditions as $key=>$filter) {
        if(is_array($filter)) {
          if(!strpos($key, "?")) {
            $this->join_conditions[]= array( "name"=>$key, "operator"=>"in", "value"=>$filter);
          }
          else $this->join_conditions[] = array("name"=>$key, "operator"=>"raw", "value"=>$filter);
        }
        else $this->join_conditions[]= array("name"=>$key,"operator"=>"=", "value"=>$filter);
      }
    }
    return $this;
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
    $this->row = $res->row;
    $this->after_insert();
    return $this;
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
 	
 	public function rows() {
 	  return $this->db->select($this);
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
 	  $this->set_attributes($array);
		return $this->save();
 	}
 	
 	public function set_attributes($array) {
 	  //move association fields to the end of the array
 	  foreach((array)$array as $k=>$v) {
 	    if($this->columns[$k]){
   	    $field = $this->get_col($k);
   	    if($field->is_association){
   	      $swap = $array[$k];
   	      unset($array[$k]);
   	      $array[$k] = $swap;
        }
      }
    }

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
   * comparison function for models
   *
   * @param WaxModel $model this is the model to compare this one to
   * @return Boolean, true if the models match, false if they don't (per column matching)
   */
  public function equals(WaxModel $model){
	  $comp_cols = array_diff_key($this->columns,array($this->primary_key => false)); //take out ID
    foreach($comp_cols as $col => $details){
      $col_type = $details[0];
      $ours = $this->$col;
      $theirs = $model->$col;
      if(in_array($col_type, array("HasManyField","ManyToManyField"))){
        $ours = $ours->rowset;
        $theirs = $theirs->rowset;
      }elseif($col_type == "ForeignKey"){
        $ours = $ours->rowset;
        $theirs = $theirs->rowset;
      }
      if($ours != $theirs) return false;
    }
    return true;
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
    if(array_key_exists($func, $this->columns)) {
      $field = $this->get_col($func);
      return $field->get($args[0]);
    }
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
