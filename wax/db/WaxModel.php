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
  protected $db;
  public $table = null;
  public $primary_key="id";
  protected $row = array();
  protected $filters = array();
  protected $limit = false;
  protected $offset = "0";
 

  /**
   *  constructor
   *  @param  mixed   param   PDO instance,
   *                          or record id (if integer),
   *                          or constraints (if array) but param['pdo'] is PDO instance
   */
 	function __construct($param=null) {
 		$this->db = new self::$adapter;
 		$class_name =  get_class($this) ;
 		if( $class_name != 'WaxModel' ) {
 			$this->table = WXInflections::underscore( $class_name );
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
 	
 	public function all() {
 	  
 	  return $this;
 	}
 	
 	public function first() {
 	  $this->limit = "1";
 	  return $this;
 	}
 	
 	public function filter($filter) {
    if(is_string($filter)) $filters = preg_split("/\s?(&|AND)\s?/i",$filter);
    if(is_array($filter)) $filters = $filter;
    foreach($filters as $filter) {
      $this->filters[]=$filter;
    }
    return $this;
 	}

  

     /**
      *  get property
      *  @param  string  name    property name
      *  @return mixed           property value
      */
 	public function __get( $name ) {
    if( array_key_exists( $name, $this->row ) return $this->row[$name];
    if(method_exists($this, $name)) return $this->{$name}();
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
 	  this->before_delete();
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



 	public function update_attributes($array) {
    
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
?>