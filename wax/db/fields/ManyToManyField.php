<?php

/**
 * ManyToManyField class
 *
 * @package PHP-Wax
 **/
class ManyToManyField extends WaxModelField {
  
  public $maxlength = "11";
  public $target_model = false; //model on the other side of the many to many
  public $join_model = false; //instance of WaxModelJoin filtered with this instance's primary key
  public $widget = "MultipleSelectInput";
  public $use_join_select = true;
	public $use_cache = true;
	
	public $load = "lazy"; // One of Lazy, Eager or none
	
	
	
	/**
	 * the setup function for this field is different, as this is a many to many relationship it takes into 
	 * account both sides of the relationship, initialises the join table if its missing and preps the 
	 * filter
	 */
  public function setup() {
    $this->col_name = false;
    if(!$this->target_model) $this->target_model = Inflections::camelize($this->field, true);
    $j = new $this->target_model;
    if(strnatcmp($this->model->table, $j->table) <0) {
      $left = $this->model;
      $right = $j;
    } else {
      $left = $j;
      $right = $this->model;
    }
    $join = new WaxModelJoin();
    $join->init($left, $right);
    $join->syncdb();
    $this->join_model = $join->filter(array($this->join_field($this->model) => $this->model->primval));
  }

  public function validate() {
    return true;
  }
  
  
  
  /**
	 * Reads the load strategy from the setup and delegates either to eager_load or lazy load
	 * @return WaxModelAssociation
	 */	
  public function get() {
    if($this->load=="eager") return $this->eager_load();
    return $this->lazy_load();
  }


  private function eager_load() {
    $target = new $this->target_model;
		$target_prim_key_def = $target->table.".".$target->primary_key;
		$conditions = "( $target_prim_key_def = ".$this->join_model->table.".".$this->join_field($target)." AND ";
		$conditions.= $this->join_model->table.".".$this->join_field($this->model)." = ".$this->model->primval." )";
		$this->join_model->select_columns = array($target->table.".*");
		$cache = WaxModel::get_cache($this->target_model, $this->field, $this->model->primval,$vals->rowset, false);
		if($cache) return new WaxModelAssociation($this->model, $target, $cache);
		$vals = $this->join_model->clear()->left_join($target->table)->join_condition($conditions)->filter("$target_prim_key_def > 0")->all();
		 WaxModel::set_cache($this->target_model, $this->field, $this->model->primval, $vals->rowset);
		return new WaxModelAssociation($this->model, $target, $vals->rowset);
  }
  
  private function lazy_load() {
    $target_model = new $this->target_model;
    $left_field = $this->model->table."_".$this->model->primary_key;
    $right_field = $target_model->table."_".$target_model->primary_key;
    $this->join_model->select_columns=$right_field;
    $ids = array();
    if($this->load =="lazy" && $cache = WaxModel::get_cache(get_class($this->model),$this->field, $this->model->id, false )) {
      return new WaxModelAssociation($this->model, $target_model, $cache, $this->field);      
    }
    foreach($this->join_model->rows() as $row) $ids[]=$row[$right_field];
    if($this->load =="lazy") {
      WaxModel::set_cache(get_class($this->model),$this->field, $this->model->id , $ids);
      return new WaxModelAssociation($this->model, $target_model, $ids, $this->field);
    }
    return new WaxModelAssociation($this->model, $target_model->filter(array($target_model->primary_key=>$ids)), array(), $this->field);
  }
  
  
	/**
	 * clever little function that sets values across the join so $origin->many_to_many = $value works like:
	 *  - loops each wax model (or element in recordset)
	 *  - creates a new record on the join table for each
	 *  - clears the cache (so that any 'gets' are accurate)
	 * @param mixed $value - waxmodel or waxrecordset
	 */	
  public function set($value) {
    if($value instanceof WaxModel) {
      if(!$this->join_model->filter(array($this->join_field($value) => $value->primval) )->all()->count() ) {
        $new = array($this->join_field($value)=>$value->primval, $this->join_field($this->model) => $this->model->primval);
        $this->join_model->create($new);
      }
    }
    if($value instanceof WaxRecordset) {
      foreach($value as $join) {
        $existing = clone $this->join_model;
        $filter = false;
        // Check for an existing association
        $filter = array($this->join_field($join) => $join->primval);
        $existing = $existing->filter($filter)->all();
        if(!$existing->count()) {
          $new = array($this->join_field($join)=>$join->primval, $this->join_field($this->model) => $this->model->primval);
          $this->join_model->create($new);
        }
      }
    }
    WaxModel::unset_cache(get_class($this->model), $this->field, $this->model->primval);
  }
  /**
   * this unset function removes any link between the origin and target
   * again the cache is cleared so any 'get' calls return accurate data
   * @param string $model 
   */  
  public function unlink($model) {
    $links = new $this->target_model;

    WaxModel::unset_cache(get_class($this->model), $this->field, $this->model->primval);

    if($model instanceof WaxModel) {
      $id = $model->primval;
      $this->join_model->filter(array($links->table."_".$links->primary_key => $id))->delete();
    }
    if($model instanceof WaxRecordset) {
      foreach($model as $obj) {
        $id = $obj->primval;
        $filter[]= $links->table."_".$links->primary_key."=".  $id;
      }
      $this->join_model->filter("(".join(" OR ", $filter).")")->delete();
    }
    return $this->join_model;
  }
  
	/**
	 * as this is a many to many, the delete doesnt actually delete!
	 * instead it unlinks the join table & yes, the obligatory cache clearing 
	 */	
	public function delete(){
    WaxModel::unset_cache(get_class($this->model), get_class($this->target_model), $this->model->primval);
		//delete join tables!
		$data = $this->model->{$this->field};
		if($data->count()) $this->unlink($data);
	}
	/**
	 * as a save on a many to many doesn't do anything, just return true
	 */
  public function save() {
    return true;
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
    if($this->model->identifier) {
      $this->choices[""]="Select";
      foreach($j->all() as $row) $this->choices[$row->{$row->primary_key}]=$row->{$row->identifier};
    }
    return $this->choices;
  }
  
	/**
	 * super smart __call method - passes off calls to the target model (deletes etc)
	 * @param string $method 
	 * @param string $args 
	 * @return mixed
	 */	
  public function __call($method, $args) {
    $assoc = $this->get();
    $constraints = implode(",", $assoc->rowset);
    $model = clone $assoc->target_model;
    $model->filter(array($model->primary_key=>$constraints));
    return call_user_func_array(array($model, $method), $args);
  }

} 
