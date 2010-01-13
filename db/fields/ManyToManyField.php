<?php

/**
 * ManyToManyField class
 *
 * @package PHP-Wax
 **/
class ManyToManyField extends WaxModelField {
  
  public $target_model = false; //model on the other side of the many to many
  public $join_model = false; //instance of WaxModelJoin filtered with this instance's primary key
  public $join_model_class = "WaxModelJoin";
  public $widget = "MultipleSelectInput";
  public $use_join_select = true;
	public $eager_loading = false;
  public $is_association = true;
	public $join_table = false; //this chap means that you can pass any name for the join table in on define()
	public $join_order = false; //specify order of the returned joined objects
  public $data_type = "integer";
  public $loaded = false;
  
	/**
	 * the setup function for this field is different, as this is a many to many relationship it takes into 
	 * account both sides of the relationship, initialises the join table if its missing and preps the 
	 * filter
	 */
  public function setup() {
    $this->col_name = false;
    if(!$this->target_model) $this->target_model = Inflections::camelize($this->field, true);
    $this->setup_join_model();
  }

  public function setup_join_model() {
    $join = new $this->join_model_class;
    $join->table = $this->join_table;
    $join->init($this->model, new $this->target_model);
    $this->join_model = $join->filter(array($this->join_field($this->model) => $this->model->primval));
    if($this->join_order) $this->join_model->order($this->join_order); 
  }

  public function validate() {
    return true;
  }
  
  public function before_sync() {
    $this->setup_join_model();
    $this->join_model->disallow_sync = false;
   	$this->join_model->syncdb();
  }
  
  private function create_association($target = false, $rowset = array()){
    if(!$target) $target = new $this->target_model;
    return new WaxModelAssociation($this->model, $target, $rowset, $this->field);
  }

  /**
	 * Reads the load strategy from the setup and delegates either to eager_load or lazy load
	 * @return WaxModelAssociation
	 */	
  public function get($filters = false) {
    if($this->model->row[$this->field] instanceof WaxModelAssociation) return $this->model->row[$this->field];
    if(!$this->model->pk()) return $this->model->row[$this->field] = $this->create_association();
    $target = new $this->target_model;
    if($filters) $target->filter($filters);
    if($this->join_order) $target->order($this->join_order);
    if($this->eager_loading) return $this->eager_load($target);
    if(!$this->eager_loading) return $this->lazy_load($target);
  }


  private function eager_load($target) {
		$this->join_model->left_join($target);
		$this->join_model->join_condition("$target->table.$target->primary_key = ".$this->join_model->table.".".$this->join_field($target));
		//select columns from the far side of the join, not the join table itself
		$this->join_model->select_columns = array($target->table.".*");
		foreach(array_diff_key($this->join_model->columns(),array($this->join_model->primary_key=>false,$this->join_model->left_field=>false,$this->join_model->right_field=>false)) as $col => $col_options)
		  $this->join_model->select_columns[] = "{$this->join_model->table}.$col";
		$vals = $this->join_model->all();
		$this->loaded = true;
		return $this->model->row[$this->field] = $this->create_association($target, $vals->rowset);
  }
  
  private function lazy_load($target) {   
    $left_field = $this->model->table."_".$this->model->primary_key;
    $right_field = $target->table."_".$target->primary_key;
    $this->join_model->select_columns=$right_field;
    $ids = array();
    foreach($this->join_model->rows() as $row) $ids[]=$row[$right_field];
    return $this->model->row[$this->field] = $this->create_association($target, $ids);
  }
  
  
	/**
	 * clever little function that sets values across the join so $origin->many_to_many = $value works like:
	 *  - loops each wax model (or element in recordset)
	 *  - creates a new record on the join table for each
	 * @param mixed $value - waxmodel or waxrecordset
	 */	
  public function set($value) {
    if($value instanceof WaxModel) {
      $this->get()->add($value);
    }elseif($value instanceof WaxRecordset) {
      $existing = $this->get();
      foreach($value as $val) $existing->add($val);
    }
  }
  /**
   * this unset / delete function removes any link between the origin and target
   * @param string $model 
   */
	public function delete($model = false){return $this->unlink($model);}
  public function unlink($model = false) {
    if(!$this->model->primval) return $this->join_model; //if we don't know what to unlink from we can't unlink anything, just do nothing and return
    if(!$model) $model = $this->get(); //if nothing gets passed in to unlink then unlink everything
    $links = new $this->target_model;
    if($model instanceof WaxRecordset) {
      foreach($model as $obj) $filter[] = $obj->primval;
      if(count($filter)) $this->join_model->filter($links->table."_".$links->primary_key,$filter)->delete();
    }else{
      if($model instanceof WaxModel) $model = $model->primval();
      $this->join_model->filter($links->table."_".$links->primary_key, $model)->delete();
    }
    return $this->join_model;
  }
  
	/**
	 * as a save on a many to many doesn't do anything, just return true
	 */
  public function save() {
    return true;
    //return $this->set($this->value);
  }
  
  /**
   * filter on the join_model
   * IMPORTANT: will only work with array filters, text filters are passed on to the target model as usual
   * ALSO IMPORTANT: will not work if any of the defined filters are on columns not in the join model, in that case it will also pass on to the target model as usual
   *
   * @return WaxModelAssociation
   */
  
  public function filter($params, $value=NULL, $operator="=") {    
    if(is_array($params)){
      foreach($params as $column => $value){
        $cols = $this->join_model->columns();
        if(!$cols[$column]) return $this->__call("filter", array($params, $value, $operator));
      }
      $this->join_model->filter($params, $value, $operator);
      return $this->get();
    }else return $this->__call("filter", array($params, $value, $operator));
  }

	/**
	 * take the model and create a string version of the field to use in the join
	 * @param string $WaxModel 
	 */	
  protected function join_field(WaxModel $model) {
    return $model->table."_".$model->primary_key;
  }
	/**
	 * get the choices for the field
	 * @return array
	 */	
  public function get_choices() {
    $j = new $this->target_model;
    if($this->identifier) $j->identifier = $this->identifier;
    foreach($j->all() as $row) $this->choices[$row->{$row->primary_key}]=$row->{$row->identifier};
    return $this->choices;
  }
  
  /**
   * this is used to defer writing of associations to the database adapter
   * i.e. this will only be run if this field's parent model is saved
   */
  public function save_assocations($model_pk, &$rowset){
    foreach($rowset as $index => $row){
      $target = new $this->target_model;
      $target->row = &$rowset[$index];
      if(!$target->pk()) $target->save();
      
      $join_model = clone $this->join_model;
      $join_model->{$this->join_field($this->model)} = $model_pk;
      $join_model->{$this->join_field($target)} = $target->pk();
      $join_model->save();
    }
  }

	/**
	 * super smart __call method - passes off calls to the target model (deletes etc)
	 * @param string $method 
	 * @param string $args 
	 * @return mixed
	 */	
  public function __call($method, $args) {
    $assoc = $this->get();
    $model = clone $assoc->target_model;
    if($this->eager_loading){
      $key = $model->primary_key;
      foreach((array) $assoc->rowset as $row) $filter_args[$key][] = $row[$key];
    }else $filter_args = array($model->primary_key=>$assoc->rowset);
    if($assoc->rowset) $model->filter($filter_args);
    else $model->filter("1 = 2");
    return call_user_func_array(array($model, $method), $args);
  }

} 
?>