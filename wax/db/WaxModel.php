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
  protected $db = false;
  public $table = false;
  public $primary_key="id";
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
 		$this->db = self::$adapter;
 		$class_name =  get_class($this) ;
 		if( $class_name != 'WaxModel' && !$this->table ) {
 			$this->table = WXInflections::underscore( $class_name );
 		}
 		if($params) {
 		  $res = $this->filter(array($this->primary_key=>$params))->first();
 		  $this->row = $res->row;
 		}
 		$this->setup();
 	}
 	
 	static public function load_adapter($db_settings) {
 	  $adapter = "Wax".ucfirst($db_settings["dbtype"])."Adapter";
 	  self::$adapter = new $adapter($db_settings);
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
        $this->filters[]= $key."=".$this->db->quote($filter);
      }
    }
    return $this;
 	}
 	
 	public function validate() {
 	  foreach($this->columns as $column=>$setup) {
 	    $field = new $setup[0]($column, $this, $setup[1]);
 	    $field->validate();
 	    if(count($field->errors) >0) {
 	      error_log($column." has errors");
 	      $this->errors[$column] = $field->errors;
      }
 	  }
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
      if($this->columns[$name]=="ForeignKey") {
        $field = new $this->columns[$name][0]($name, $this, $this->columns[$name][1]);
        return $field->get();
      }
      return $this->row[$name];
    }
    elseif(method_exists($this, $name)) return $this->{$name}();
  }


  /**
   *  set property
   *  @param  string  name    property name
   *  @param  mixed   value   property value
   */
 	public function __set( $name, $value ) {
    $this->row[$name] = $value;
  }



     /**
      *  insert record to table, or update record data
      */
 	public function save() {
 	  $this->before_save();
 	  if(!$this->validate) throw new WXUserException(print_r($this->errors, 1));
 	  if($this->row[$this->primary_key]) $res = $this->update();
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
 		$class_name =  get_class($this);
 		$row = new $class_name();
 		$row->set_attributes($attributes);
 		$row->save();
 		print_r($row); exit;
 		return $row;
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



 	public function __call( $func, $args ) {
 	  return array();
 	  $function = explode("_", $func);
 		if(array_key_exists($function[1], $this->has_many_throughs) && count($function)==2) {
 			return $this->has_many_methods($function[0], $function[1], $args);
 		} else return $this->dynamic_finders($func, $args);
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