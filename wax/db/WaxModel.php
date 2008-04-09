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
  protected $db = false;
  public $table = false;
  public $primary_key="id";
  public $primary_type = "AutoField";
  public $primary_options = array();
  public $row = array();
  public $columns = array();
  public $filters = array();
  public $order = false;
  public $limit = false;
  public $offset = "0";
  public $errors = array();
 

  /**
   *  constructor
   *  @param  mixed   param   PDO instance,
   *                          or record id (if integer),
   *                          or constraints (if array) but param['pdo'] is PDO instance
   */
 	function __construct($params=null) {
 		$this->db = new self::$adapter(self::$db_settings);
 		$class_name =  get_class($this) ;
 		if( $class_name != 'WaxModel' && !$this->table ) {
 			$this->table = WXInflections::underscore( $class_name );
 		}
 		if($params) {
 		  $res = $this->filter(array($this->primary_key => $params))->first();
 		  $this->row = $res->row;
 		}
 		$this->define($this->primary_key, $this->primary_type, $this->primary_options);
 		$this->setup();
 	}
 	
 	static public function load_adapter($db_settings) {
 	  $adapter = "Wax".ucfirst($db_settings["dbtype"])."Adapter";
 	  self::$adapter = $adapter;
 	  self::$db_settings = $db_settings;
 	}
 	
 	public function define($column, $type, $options=array()) {
 	  $this->columns[$column]=array($type, $options);
 	}
 	
 	static public function model_setup($model, $column, $type, $options= array()) {
 	  $obj = new $model;
 	  $obj->define($column, $type, $options);
 	  return $obj->syncdb();
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
 	
 	public function clear() {
 	  $this->columns = array();
    $this->filters = array();
    $this->order = false;
    $this->limit = false;
    $this->offset = "0";
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

  public function get_col($name) {
    return new $this->columns[$name][0]($name, $this, $this->columns[$name][1]);
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
      *  insert record to table, or update record data
      */
 	public function save() {
 	  $this->before_save();
 	  if(!$this->validate) return false;
 	  foreach($this->columns as $col=>$setup) $this->get_col($col)->save();
 	  if($this->primval) $res = $this->update();
 	  else $res = $this->insert();
 		$this->after_save();
 		return $res;
  }

    /**
     *  delete record from table
     *  @param  mixed id    record id
     *  @return boolean
     */
 	public function delete() {
 	  $this->before_delete();
 	  $res = $this->db->delete($this);
    $this->after_delete();
    return $res;
  }

 
  public function update( $id_list = array() ) {
    $this->before_update();
    $res = $this->db->update($this);
    $this->after_update();
    return $res;
  }

  public function insert() {
    $this->before_insert();
    $res = $this->db->insert($this);
    $this->after_insert();
    return $res;
  }
  
  public function syncdb() {
    $res = $this->db->syncdb($this);
    return $res;
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
 	  $row->set_attributes($res[0]);
 	  return $row;
 	}


 	public function update_attributes($array) {
    foreach($array as $k=>$v) {
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



 	public function __call( $func, $args ) {
 	  return array();
 	  $function = explode("_", $func);
 		if(array_key_exists($function[1], $this->has_many_throughs) && count($function)==2) {
 			return $this->has_many_methods($function[0], $function[1], $args);
 		} else return $this->dynamic_finders($func, $args);
  }
   
  public function __clone() {
  	$this->setup();
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