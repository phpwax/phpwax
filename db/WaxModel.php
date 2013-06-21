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
  public $table = false;
  public $primary_key="id";
  public $primary_type = "AutoField";
  public $primary_options = array();
  public $row = array();
  public $columns = array();
  public $select_columns = array();
  public $filters = array();
  public $group_by = false;
  public $_group_by_params = array();
  public $having = false;
  public $_order = false;
  public $_order_params = array();
  public $_limit = false;
  public $_offset = "0";
  public $sql = false;
  public $errors = array();
  public $persistent = true;
  public $identifier = false;
  static public $object_cache = array();
  public $is_paginated = false;
  public $validation_groups = array(); //active validation groups to check on validate
  //joins
  public $is_left_joined = false;
  public $left_join_target = false;
  public $left_join_table_name = false;
  public $join_conditions = false;
  public $cols = array();
  public $_col_names = array();
  public $_update_pk = false;


  /** interface vars **/
  public $cache_enabled = false;
  public $cache_lifetime = 600;
  public $cache_engine = "Memory";
  public $cache = false;

  public $asked_for_scope;

  /**
   *  constructor
   *  @param  mixed   param   PDO instance,
   *                          or record id (if integer),
   *                          or constraints (if array) but param['pdo'] is PDO instance
   */
  function __construct($params=null) {
    try {
      if(!static::$db && static::$adapter) static::$db = new static::$adapter(static::$db_settings);
    } catch (Exception $e) {
      throw new WaxDbException("Cannot Initialise DB", "Database Configuration Error");
    }

    $class_name =  get_class($this) ;
    if( $class_name != 'WaxModel' && !$this->table ) {
      $this->table = Inflections::underscore( $class_name );
    }


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

  static public function find($finder, $params = array(), $scope_params = array()) {
    $class = get_called_class();
    if(is_numeric($finder)) return new $class($finder);
    if(is_array($params)) {
      $mod = new $class;
      foreach($params as $method=>$args) {
        call_user_func_array(array($mod,$method), $args);
      }
    } elseif(is_string($params)) {
      $mod = new $class($params);
      foreach($scope_params as $method=>$args) {
        call_user_func_array(array($mod,$method), $args);
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



  static public function load_adapter($db_settings) {
    if($db_settings["dbtype"]=="none") return true;
    $adapter = "Wax".ucfirst($db_settings["dbtype"])."Adapter";
    static::$adapter = $adapter;
    static::$db_settings = $db_settings;
  }

  public function define($column, $type, $options=array()) {
    $this->columns[$column]=array($type, $options);
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

  public function search($text, $columns = array(), $relevance=0) {
    $res = static::$db->search($this, $text, $columns, $relevance);
    return $res;
  }

  /**
   * Scope function... allows a named scope function to be called which configures a view of the model
   *
   * @param string $scope
   * @return $this
   */

  public function scope($scope) {
    $this->asked_for_scope = $scope;
    $method = "scope_".$scope;
    WaxEvent::run(get_class($this).".scope", $this);
    WaxEvent::run("model.".get_class($this).".scope", $this);
    if(method_exists($this, $method)) $this->$method;
    return $this;
  }


  public function clear() {
    $this->filters = array();
    $this->group_by = false;
    $this->_order = false;
    $this->_limit = false;
    $this->_offset = "0";
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
    if(!$this->columns[$name][0]) throw new WXException("Error", $name." is not a valid call");
    return new $this->columns[$name][0]($name, $this, $this->columns[$name][1]);
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
      if(WaxModelField::$skip_field_delegation_cache[$this->columns[$name][0]]["get"]) return $this->row[$name];
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
    WaxEvent::run("wax.model.before_save", $this);
    $this->before_save();
    $associations = array();
    foreach($this->columns as $col=>$setup) {
      $field = $this->get_col($col);
      $this->_col_names[$field->col_name] = 1; //cache column names as keys of an array for the adapter to check which cols are allowed to write
      if(!$field->is_association) $this->get_col($col)->save();
      else $associations[]=$field;
    }
    if($this->persistent) {
      if($this->primval) {
        $this->before_update();
        if(!$this->validate()) return false;
        $res = $this->update();
      }
      else {
        $this->before_insert();
        if(!$this->validate()) return false;
        $res = $this->insert();
      }
    }
    foreach($associations as $assoc) $assoc->save();
    WaxEvent::run("wax.model.after_save", $this);
    $res->after_save();
    return $res;
  }

    /**
     *  delete record from table
     *  @return model
     */
  public function delete(){
    //throw an exception trying to delete a whole table.
    if(!$this->filters && !$this->primval) throw new WaxException("Tried to delete a whole table. Please revise your code.");
    $this->before_delete();
    //before we delete this, check fields - clean up joins by delegating to field
    foreach($this->columns as $col=>$setup) $this->get_col($col)->delete();
    $res = static::$db->delete($this);
    $this->after_delete();
    return $res;
  }

  public function order($order_by, $order_params = array()){
    $this->_order = $order_by;
    $this->_order_params = $order_params;
    return $this;
  }

  public function random($limit) {
    $this->order(static::$db->random());
    $this->limit($limit);
    return $this;
  }

  public function dates($start, $end) {

  }
  public function having($condition){
    $this->having = $condition;
    return $this;
  }

  public function offset($offset){
    $this->_offset = $offset;
    return $this;
  }
  public function limit($limit){
    $this->_limit = $limit;
    return $this;
  }
  public function group($group_by, $group_params = array()){
    $this->group_by = $group_by;
    $this->_group_by_params = $group_params;
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
    $this->is_left_joined = true;
    if($target instanceof WaxModel){
      $this->left_join_table_name = $target->table;
      $this->left_join_target = $target;
    }else $this->left_join_table_name = $target;
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
    $res = static::$db->update($this);
    $res->after_update();
    return $res;
  }

  public function insert() {
    $res = static::$db->insert($this);
    $this->row = $res->row;
    $this->after_insert();
    return $this;
  }

  public function syncdb() {
    if(get_class($this) == "WaxModel") return;
    if($this->disallow_sync) return;
    $res = static::$db->syncdb($this);
    return $res;
  }

  public function query($query) {
    return static::$db->query($query);
  }


  public function create($attributes = array()) {
    $row = clone $this;
    return $row->update_attributes($attributes);
  }


  public function all() {
    return new WaxRecordset($this, static::$db->select($this));
  }

  public function rows() {
    return static::$db->select($this);
  }


  public function first() {
    $this->_limit = "1";
    $model = clone $this;
    $res = static::$db->select($model);
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
      if($this->columns[$k]){
        $is_assoc = WaxModelField::$skip_field_delegation_cache[$this->columns[$k][0]]['assoc'];
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
   * primval() function - superseded by snappier pk()
   *
   * @return mixed
   * simple helper to return the value of the primary key
   **/
  public function primval() {return $this->pk();}

  public function pk() {
    return $this->{$this->primary_key};
  }



  /**
   * get the fields that aren't stored on the row, but are farmed out from other places, in the core wax this is HasManyField and ManyToManyField
   */
  public function associations(){
    $ret = array();
    foreach($this->columns as $column => $data){
      $type = $data[0];
      if(($type == "HasManyField" || $type == "ManyToManyField") && $data[1]['associations_block'] !== true) $ret[$column] = $data;
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
    $res = static::$db->select($this);
    return new WaxRecordset($this, $res);
  }



  public function __call( $func, $args ) {
    if(array_key_exists($func, $this->columns)) {
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

      if($finder[0]=="find_all_") return static::find("all", array("filter"=>$filter));
      else return static::find("first", array("filter"=>$filter));
    }
  }

  public function __clone() {
    $this->setup();
   }

  public function total_without_limits(){
    return static::$db->total_without_limits;
  }


  /**
   * take the column name you pass in and return a pretty version
   * - if no column is passed, finds the first non auto/id field and uses that
   * - from the column data try to work out the formatting; date time returns a parsed time stamp
   * - joins recursively call itself by using the humanize_join function
   * - if all else fails call it like a function and see what happens
   */
  public function humanize($column=false){
    if(!$column && $this->identifier != $this->primary_key && $this->columns[$this->identifier][0] != "IntegerField") $column = $this->identifier;
    elseif(!$column){
      foreach($this->columns as $k=>$v){
        if(($v[0] != "IntegerField" && $v[0] != "AutoField") || (count($v[1]['choices']))){
          $column = $k;
          break;
        }
      }
    }
    if(($col_info = $this->columns[$column]) && ($type = $col_info[0]) && ($info = $col_info[1])){
      if(count($info['choices']) && ($val = $this->$column) !== false) return $info['choices'][$val];
      if($type == "DateTimeField") return ($val = $this->$column)?date(($info['output_format'])?$info['output_format']:"jS F Y H:i", strtotime($val)):'';
      else if(($type == "ForeignKey" || $type == "ManyToManyField" || $type == "HasManyField") && ($join = $this->$column)) return $this->humanize_join($join);
    }
    return $this->$column();
  }
  /**
   * go over the join and look for the correct column to return by calling humanize on the other side
   */
  protected function humanize_join($join){
    if($join instanceOf WaxModel && $join->identifier != $join->primary_key) return $join->humanize($join->identifier);
    else if($join instanceOf WaxModel && ($cols = $join->columns)) return $join->humanize();
    else if($join instanceOf WaxRecordSet){
      $str = "";
      foreach($join as $r) $str .= $r->humanize().", ";
      return trim($str, ", ");
    }
    return "";
  }


   /**
    *  These are left deliberately empty in the base class
    *
    */

  public function setup(){
   WaxEvent::run(get_class($this).".setup", $this);
    WaxEvent::run("model.".get_class($this).".setup", $this);
  }
  public function before_save(){
    WaxEvent::run(get_class($this).".before_save", $this);
    WaxEvent::run("model.".get_class($this).".save.before", $this);
  }
  public function after_save(){
    WaxEvent::run(get_class($this).".after_save", $this);
    WaxEvent::run("model.".get_class($this).".save.after", $this);
  }
  public function before_update(){
    WaxEvent::run(get_class($this).".before_update", $this);
    WaxEvent::run("model.".get_class($this).".update.before", $this);
  }
  public function after_update(){
    WaxEvent::run(get_class($this).".after_update", $this);
    WaxEvent::run("model.".get_class($this).".update.after", $this);
  }
  public function before_insert(){
    WaxEvent::run(get_class($this).".before_insert", $this);
    WaxEvent::run("model.".get_class($this).".insert.before", $this);
  }
  public function after_insert(){
    WaxEvent::run(get_class($this).".after_insert", $this);
    WaxEvent::run("model.".get_class($this).".insert.after", $this);
  }
  public function before_delete(){
    WaxEvent::run(get_class($this).".before_delete", $this);
    WaxEvent::run("model.".get_class($this).".delete.before", $this);
  }
  public function after_delete(){
    WaxEvent::run(get_class($this).".after_delete", $this);
    WaxEvent::run("model.".get_class($this).".delete.after", $this);
  }


}
