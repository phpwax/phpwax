<?php

/**
 * Base Database Class
 *
 * @package PHP-Wax
 * @author Ross Riley
 *
 * Allows models to be mapped to application objects
 **/
class WaxModel{

  static public $adapter = false;
  static public $db_settings = false;
  static public $db = false;
  static public $columns = array();

  public $table = false;
  public $primary_key="id";
  public $primary_type = "AutoField";
  public $primary_options = array();
  public $row = array();
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
	public $cols = array();


	/** interface vars **/
	public $cache_enabled = false;
  public $cache_lifetime = 600;
  public $cache_engine = "Memory";
  public $cache = false;

  /**
   *  constructor
   *  @param  mixed   param   PDO instance,
   *                          or record id (if integer),
   *                          or constraints (if array) but param['pdo'] is PDO instance
   */
 	function __construct($params=null) {
 	  try {
 	    if(!self::$db && self::$adapter) self::$db = new self::$adapter(self::$db_settings);
 	  } catch (Exception $e) {
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
  
  static public function find($finder, $params = array()) {
    $class = get_called_class();
    if(is_numeric($finder)) return new $class($finder);
    $mod = new $class;
    foreach($params as $method=>$args) {
      $mod->$method($args);
    }
    switch($finder) {
      case 'all': $mod = new $class;
        return $mod->all();
        break;
      case 'first': $mod = new $class;
        return $mod->first();
        break;
    }
  }
 
 	static public function load_adapter($db_settings) {
 	  if($db_settings["dbtype"]=="none") return true;
 	  $adapter = "Wax".ucfirst($db_settings["dbtype"])."Adapter";
 	  self::$adapter = $adapter;
 	  self::$db_settings = $db_settings;
 	}

 	public function define($column, $type, $options=array()) {
 	  self::$columns[$column]=array($type, $options);
 	}

 	public function add_error($field, $message) {
 	  if(!in_array($message, (array)$this->errors[$field])) $this->errors[$field][]=$message;
 	}

 	public function filter($column, $value=NULL, $operator="=") {
 	  //if the var is a string, then we are asuming its a new style filter
 	  if(is_string($column)) {
 	    //with a value passed in this confirms its a new method of filter
 	    if($value !== NULL) {
 	      //operator sniffing
        if(is_array($value))
          if(strpos($column, "?") === false) $operator = "in"; //no ? params so this is an old in check
          else $operator = "raw"; //otherwise its a raw operation, so substitue values

        $filter = array("name"=>$column,"operator"=>$operator, "value"=>$value);
        if($operator == "=") $this->filters[$column] = $filter; //if its equal then overwrite the filter passed on col name
        else $this->filters[] = $filter;

 	    } else $this->filters[] = $column; //assume a raw query, with no parameters
    }else{ //if the column isn't a string, then we assume it's an array with multiple filter's passed in.
      foreach((array)$column as $old_column => $old_value) {
        $this->filter($old_column, $old_value);
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
 	  $res = self::$db->search($this, $text, $columns);
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
 	  foreach(self::$columns as $column=>$setup) {
 	    $field = new $setup[0]($column, $this, $setup[1]);
 	    $field->is_valid();
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

  public function get_col($name) {
    if(!self::$columns[$name][0]) throw new WXException("Error", $name." is not a valid call");
    return new self::$columns[$name][0]($name, $this, self::$columns[$name][1]);
  }
  
  public function columns() {
    return self::$columns;
  }

  static public function get_cache($model, $field, $id, $transform = true) {
    $cache = new WaxCache($model."/".$field."/".$id, "memory");
    $data = unserialize($cache->get());
    if(!$transform) return $data;
    if($data) {
      $model_this = new $model;
      if(is_array($data[0])) {
     	  return new WaxRecordset(new $model, $data);
      }else{
        $row = new $model;
        $row->row = $data;
        return $row;
      }
    }
    return false;
  }

  static public function set_cache($model, $field, $id, $value) {
    $cache = new WaxCache($model."/".$field."/".$id, "memory");
    if($value instanceof WaxModel)
      $cache->set(serialize($value->row));
    elseif($value instanceof WaxRecordSet)
      $cache->set(serialize($value->rowset));
    else $cache->set($value);
  }

	static public function unset_cache($model, $field, $id = false){
    $cache = new WaxCache($model."/".$field."/".$id, "memory");
    $cache->expire();
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
    foreach(self::$columns as $name=>$col) {
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
    if(array_key_exists($name, self::$columns)) {
      if(WaxModelField::$skip_field_delegation_cache[self::$columns[$name][0]]["get"]) return $this->row[$name];
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
    if(array_key_exists($name, self::$columns)) {
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
 	  foreach(self::$columns as $col=>$setup) $this->get_col($col)->save();
 	  if(!$this->validate) return false;
    if($this->primval) $res = $this->update();
    else $res = $this->insert();
 		$res->after_save();
 		return $res;
  }

    /**
     *  delete record from table
     *  @return model
     */
 	public function delete() {
 	  //throw an exception trying to delete a whole table.
 	  if(!$this->filters && !$this->primval) throw new WaxException("Tried to delete a whole table. Please revise your code.");
 	  $this->before_delete();
		//before we delete this, check fields - clean up joins by delegating to field
		foreach(self::$columns as $col=>$setup) $this->get_col($col)->delete();
 	  $res = self::$db->delete($this);
    $this->after_delete();
    return $res;
  }

 	public function order($order_by){
		$this->order = $order_by;
		return $this;
	}

	public function random($limit) {
	  $this->order(self::$db->random());
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


	/************** Methods that hit the database ****************/


  public function update( $id_list = array() ) {
    $this->before_update();
    $res = self::$db->update($this);
    $res->after_update();
    return $res;
  }

  public function insert() {
    $this->before_insert();
    $res = self::$db->insert($this);
    $this->row = $res->row;
    $this->after_insert();
    return $this;
  }

  public function syncdb() {
    if(get_class($this) == "WaxModel") return;
    if($this->disallow_sync) return;
    $res = self::$db->syncdb($this);
    return $res;
  }

  public function query($query) {
    return self::$db->query($query);
  }


  public function create($attributes = array()) {
 		$row = clone $this;
 		return $row->update_attributes($attributes);
  }


 	public function all() {
 	  return new WaxRecordset($this, self::$db->select($this));
 	}

 	public function rows() {
 	  return self::$db->select($this);
 	}


 	public function first() {
 	  $this->limit = "1";
 	  $model = clone $this;
 	  $res = self::$db->select($model);
 	  if($res[0])
 	    $model->row = $res[0];
 	  else
 	    $model = false;
 	  return $model;
 	}


 	public function update_attributes($array) {
 	  $this->set_attributes($array);
		return $this->save();
 	}


 	/************ End of database methods *************/


 	public function set_attributes($array) {
 	  //move association fields to the end of the array
 	  foreach((array)$array as $k=>$v) {
 	    if(self::$columns[$k]){
 	      $is_assoc = WaxModelField::$skip_field_delegation_cache[self::$columns[$k][0]]['assoc'];
        if(!isset($is_assoc)){
     	    $field = $this->get_col($k);
     	    $is_assoc = $field->is_association;
 	      }
   	    if($is_assoc){
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
   * get the fields that aren't stored on the row, but are farmed out from other places, in the core wax this is HasManyField and ManyToManyField
   */
  public function associations(){
    $ret = array();
    foreach(self::$columns as $column => $data){
      $type = $data[0];
      if($type == "HasManyField" || $type == "ManyToManyField") $ret[$column] = $data;
    }
    return $ret;
  }

  /**
   * comparison function for models
   *
   * @param WaxModel $model this is the model to compare this one to
   * @return Boolean, true if the models match, false if they don't (per column matching)
   */
  public function equals(WaxModel $model){
    $skip_cols = array($this->primary_key => false);
    if(array_diff_key($this->row, $skip_cols) != array_diff_key($model->row, $skip_cols)) return false;
    foreach($this->associations() as $assoc => $data){
      $this_assoc = $this->$assoc->rowset;
      $model_assoc = $model->$assoc->rowset;
      sort($this_assoc);
      sort($model_assoc);
      if($this_assoc != $model_assoc) return false;
    }
    return true;
  }

  /**
   * returns a copied row
   * if there are associations the row will have a new primary key otherwise it will have no primary key ready to be saved
   */
  public function copy($dest = false){
    if($dest){
      $ret = clone $this;
      $ret->{$ret->primary_key} = $dest->primval();
      if($assocs = $this->associations()){
        if(!$ret->primval()) $ret->save();
        foreach($assocs as $assoc => $data){
          $ret->$assoc->unlink();
          $ret->$assoc = $this->$assoc;
        }
      }
      return $ret;
    }else{
      $ret = clone $this;
      $ret->{$ret->primary_key} = false;
      return $this->copy($ret);
    }
  }



  public function find_by_sql($sql) {
    $this->sql($sql);
    $res = self::$db->select($this);
    return new WaxRecordset($this, $res);
  }



 	public function __call( $func, $args ) {
    if(array_key_exists($func, self::$columns)) {
      $field = $this->get_col($func);
      return $field->get($args[0]);
    }
  }

  public function __clone() {
  	$this->setup();
   }

	public function total_without_limits(){
		return self::$db->total_without_limits;
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
