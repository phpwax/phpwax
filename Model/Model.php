<?php
namespace Wax\Model;
use Wax\Core\Event;
use Wax\Core\ObjectManager;
use Wax\Model\Fields;
use Wax\Template\Helper\Inflections;
use Wax\Db\DbException;


/**
 * Base Database Class
 *
 * @package PHP-Wax
 * @author Ross Riley
 *
 * Allows models to be mapped to application objects
 **/
class Model{

  static public $adapter      = FALSE;
  static public $db_settings  = FALSE;
  static public $db           = FALSE;
  public $table               = FALSE;
  public $primary_key         = "id";
  public $primary_type        = "AutoField";
  public $primary_options     = [];
  public $row                 = [];

  public $_select_columns     = [];
  public $_filters            = [];
	public $_group_by           = FALSE;
	public $_having             = false;
  public $_order              = false;
  public $_limit              = false;
  public $_offset             = "0";
  public $_include            = [];
  public $_sql                = false;
  public $_errors             = [];
  public $_persistent         = TRUE;
  public $_identifier         = FALSE;

	public $_is_paginated       = FALSE;
	
  
	public $_update_pk          = FALSE;

  public $_schema             = FALSE;
  public $_schema_class       = "Wax\\Db\\Schema";
  public $_observers          = [];

  /**
   *  constructor
   *  @param  mixed   param   PDO instance,
   *                          or record id (if integer),
   *                          or constraints (if array) but param['pdo'] is PDO instance
   */
 	function __construct($params=null) {
 	  try {
 	    self::load_adapter(self::$db_settings);
 	    if(!self::$db && self::$adapter) self::$db = new self::$adapter(self::$db_settings);
 	  } catch (\Exception $e) {
 	    throw new DbException("Cannot Initialise DB", "Database Configuration Error");
 	  }
    
 		$class_name =  get_class($this);
 		if( $class_name != 'Model' && !$this->table ) {
 			$this->table = Inflections::underscore( $class_name );
 		}
    
    $this->schema();
 		$this->define($this->primary_key, $this->primary_type, $this->primary_options);
 		$this->setup();
 		$this->set_identifier();
 		// If a scope is passed into the constructor run a method called scope_[scope]().
 		if($params) {
 		  $method = "scope_".$params;
	    if(method_exists($this, $method)) {$this->$method;}
	    else {
	      $res = $this->filter(array($this->primary_key => $params))->first();
   		  $this->row=$res->row;
   		  $this->clear();
	    }
	  }
 	}
 
 	static public function load_adapter($db_settings, $label="default") {
 	  if($db_settings["dbtype"]=="none") return true;
 	  $adapter = "Wax\\Db\\".ucfirst($db_settings["dbtype"])."Adapter";
 	  self::$adapter = $adapter;
 	  self::$db_settings = $db_settings;
 	}

 	public function define($column, $type, $options=array()) {
    $this->schema("define", $column, $type, $options);
 	}
  
  public function observe($event, $proxy) {
    $this->_observers[$event][] = $proxy;
  }
  
  public function notify_observers($event) {
    foreach($this->_observers[$event] as $proxy) {
      $proxy->notify($event, $this);
    }
  }
  
  
  
  public function schema() {
    if(!$this->_schema) {
      $schema = new $this->_schema_class(self::$adapter);
      $this->_schema = ObjectManager::set($schema);
    }
    if(count(func_get_args())) {
      $schema = ObjectManager::get($this->_schema);
      $args = func_get_args();
      return call_user_func_array([$schema, array_shift($args)], $args);
    }
  }
  
  public function columns() {
    return $this->schema("columns");
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
        if($operator == "=") $this->_filters[$column] = $filter; //if its equal then overwrite the filter passed on col name
        else $this->_filters[] = $filter;

 	    } else $this->_filters[] = $column; //assume a raw query, with no parameters
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
 	 * @return Recordset Object
 	 */

 	public function search($text, $columns = array(), $relevance=0) {
 	  $res = self::$db->search($this, $text, $columns, $relevance);
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
    $this->_filters = array();
    $this->_order = false;
    $this->_limit = false;
    $this->_offset = "0";
    $this->_sql = false;
		$this->_is_paginated = false;
    $this->_errors = array();
		$this->_having = false;
		$this->_select_columns = array();
    return $this;
 	}


 	public function validate() {
 	  foreach($this->schema("columns") as $column=>$setup) {
      $class = $setup[0];
      if(!class_exists($class)) $class = "Wax\\Model\\Fields\\".$class;
 	    $field = new $class($column, $this, $setup[1]);
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
    return $this->schema("get_col",$name, $this);
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
    if($this->_identifier) return true;
    foreach($this->schema("columns") as $name=>$col) {
      if($col[0]=="CharField") {
        $label_field = $name;
      }
      if($label_field) {
        $this->_identifier = $label_field;
        return true;
      }
    }
  }





    /**
     *  delete record from table
     *  @return model
     */
 	public function delete() {
 	  //throw an exception trying to delete a whole table.
 	  if(!$this->_filters && !$this->primval()) throw new DbException("Tried to delete a whole table. Please revise your code.", "Programmer Fail");
 	  $this->before_delete();
		//before we delete this, check fields - clean up joins by delegating to field
		foreach($this->schema("columns") as $col=>$setup) $this->get_col($col)->delete();
 	  $res = self::$db->delete($this);
    $this->after_delete();
    return $res;
  }

 	public function order($order_by){
		$this->_order = $order_by;
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
		$this->_offset = $offset;
		return $this;
	}
	public function limit($limit){
		$this->_limit = $limit;
		return $this;
	}
	public function group($group_by){
		$this->_group_by = $group_by;
		return $this;
	}
	public function sql($query) {
	  $this->_sql = $query;
	  return $this;
	}

	//take the page number, number to show per page, return paginated record set..
	public function page($page_number="1", $per_page=10){
		$this->is_paginated = true;
		return new WaxPaginatedRecordset($this, $page_number, $per_page);
	}



	/************** Methods that hit the database ****************/
  
  /**
   *  Insert record to table, or update record data
   */
  public function save() {
    Event::run("wax.model.before_save", $this);
  	$this->before_save();
    $this->notify_observers("before_save");
  	foreach($this->schema("columns") as $col=>$setup) {
  	  $this->get_col($col)->save();
  	}
  	if($this->_persistent) {      
  	  if($this->pk()) {       
        if(!$this->validate()) return false;
  	    $res = $this->update();
      } else {      
          if(!$this->validate()) return false;        
  	      $res = $this->insert();
  	    }
  		}
 	    Event::run("wax.model.after_save", $this);
  		$res->after_save();
      $this->notify_observers("after_save");
  		return $res;
  }

  public function update() {
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
    if(get_class($this) == "Model") return;
    if($this->disallow_sync) return;
    $res = self::$db->syncdb($this);
    return $res;
  }

  public function query($query) {
    return self::$db->query($query);
  }

  

 	public function all() {
 	  return new Recordset($this, self::$db->select($this));
 	}

 	public function rows() {
 	  return self::$db->select($this);
 	}

 	public function first() {
 	  $this->_limit = "1";
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
  
	public function total_without_limits(){
		return self::$db->total_without_limits;
	}
  
  public function find_by_sql($sql) {
    $this->sql($sql);
    $res = self::$db->select($this);
    return new Recordset($this, $res);
  }
  


 	/************ End of database methods *************/


 	public function set_attributes($array) {
		foreach((array)$array as $k=>$v) {
		  $this->$k=$v;
		}
	  return $this;
	}


 	public function is_posted() {
 		if(is_array($_REQUEST[$this->table])) {
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
    return $this->pk();
  }
  
 	public function pk() {
    return $this->{$this->primary_key};
  }
  
  
  /********** Static Finder Methods ********/
  
  static public function find($finder, $params = array(), $scope_params = array()) {
    $class = get_called_class();
    if(is_numeric($finder)) return new $class($finder);
    if(is_array($params)) {
      $mod = new $class;
      foreach($params as $method=>$args) {
        $mod->$method($args);
      }
    } elseif(is_string($params)) {
      $mod = new $class($params);
      foreach($scope_params as $method=>$args) {
        $mod->$method($args);
      }
    }
    switch($finder) {
      case 'all':
        return $mod->all();
        break;
      case 'first':
        return $mod->first();
        break;
    }
  }
  
  static public function where($filters=[]) {
    $class = get_called_class();
    $mod = new $class;
    $mod->filter($filters);
    return $mod->all();
  }
  
  static public function create($attributes = []) {
 		$class = get_called_class();
    $new = new $class;
 		return $new->update_attributes($attributes);
  }
  
  



  /********** Magic Methods **************/

 	public function __call( $func, $args ) {
    if(array_key_exists($func, $this->schema(columns))) {
      $field = $this->get_col($func);
      return $field->get($args[0]);
    }
  }
  
  public static function __callStatic($func, $args) {
    $finder = explode("by", $func);
    $what=explode("and", $finder[1]);
    foreach($what as $key=>$val) $what[$key]= trim($val, "_");

    if( $args ) {
      if(count($what)==2) $filter["filter"] = array($what[0]=>$args[0], $what[1], $args[1]);
    	else $filter["filter"] = array($what[0]=>$args[0]) ;

    	if($finder[0]=="find_all_") return self::find("all", $filter);
      else return self::find("first", $filter);
    }
  }

  public function __clone() {
  	$this->setup();
   }
   
   /**
    *  get property
    *  @param  string  name    property name
    *  @return mixed           property value
    */
  public function __get($name) {
    if(in_array($name, $this->schema("keys"))|| in_array($name, $this->schema("associations"))) {
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
    if(in_array($name, $this->schema("keys"))|| in_array($name, $this->schema("associations"))) {
      $field = $this->get_col($name);
      $field->set($value);
    } else throw new SchemaException("You tried to write to a property that is not defined.","Model Assignment Error", $this, $name);
  }

  /**
   *  __toString overload
   *  @return  primary key of class
   */
  public function __toString() {
    return $this->{$this->primary_key};
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
