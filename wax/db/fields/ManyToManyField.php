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
    //$join->syncdb();
    $this->join_model = $join->filter(array($this->join_field($this->model) => $this->model->primval));
  }

  public function validate() {
    return true;
  }

  /**
   * right, this is now has 2 behaviors, by default 'use_join_select' is on so the following happens:
   *  - uses the new join functionality to link the join table to the target table
   *  - creates a join condition based on the keys on both sides
	 *  - restricts the selected data to the one side of the join
	 *
	 * However, this can be turned off and used in the old manner (more suited if lots of data; ie moves load from db to application)
	 *  - finds all records in join table
	 *  - creates a set of filters based on these results
	 *
	 * The reason for having two methods is that the join version removes an all call, saving one more db query, but the join sql
	 * can be slow on large amounts of data, so the other method is here for that purpose.
   */  
  private function setup_links($target_model) {
		if($this->use_join_select){
			$target_prim_key_def = $target_model->table.".".$target_model->primary_key;
			$conditions = "( $target_prim_key_def = ".$this->join_model->table.".".$this->join_field($target_model)." AND ";
			$conditions.= $this->join_model->table.".".$this->join_field($this->model)." = ".$this->model->primval." )";
			$this->join_model->select_columns = array($target_model->table.".*");
			return $this->join_model->clear()->left_join($target_model->table)->join_condition($conditions)->filter("$target_prim_key_def > 0");
		}else{
  		$vals = $this->join_model->all();
    	if(!$vals->count()){
      	//filter added that never evaluates since we want none of the target model's to return
      	$target_model->filter("1 = 2");
      	return new WaxRecordset($target_model, array());
    	}	
    	foreach($vals as $val) $filters[]= $target_model->primary_key."=".$val->{$this->join_field($target_model)};
  		return $target_model->filter("(".join(" OR ", $filters).")");
		}
  }
	/**
	 * Right, this figures out what to return when a join is called (ie $origin_model->many_to_many->field_or_function)
	 * it also does caching on the resulting join model query (so the waxrecordset from the all is stored)
	 * @return WaxModelAssociation
	 */	
  public function get() {
		$target_model = new $this->target_model;
		if(!$this->model->primval)
		  return new WaxRecordset($target_model->filter("1=2"), array()); //add impossible filter to the model, to match the empty rowset
		else{			
			$cached = WaxModel::get_cache(get_class($this->model), $this->field, $this->model->primval);
			if($this->use_cache && $cached) $found_rows = $cached; 
			else $found_rows = $this->setup_links($target_model)->all();
			//so we should be using the cache, but its not set, set it
			if($this->use_cache && !$cached)
				WaxModel::set_cache(get_class($this->model), $this->field, $this->model->primval, $found_rows);
			return new WaxModelAssociation($target_model, $this->model, $found_rows->rowset, $this->field);
		}
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
  
  public function get_links() {
    $target_model = new $this->target_model;
    $this->join_model->select_columns=$this->join_model->right_field;
    print_r(array_values($this->join_model->rows())); exit;
    return new WaxModelAssociation($target_model, $this->model, $this->join_model->rows());
  }
  
  
	/**
	 * super smart __call method - passes off calls to the target model (deletes etc)
	 * @param string $method 
	 * @param string $args 
	 * @return mixed
	 */	
  public function __call($method, $args) {
    $target_model = new $this->target_model;
    return call_user_func_array(array($this->setup_links($target_model), $method), $args);
  }

} 
