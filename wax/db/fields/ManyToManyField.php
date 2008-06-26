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
   * right, this is now has 2 behaves, by default 'use_join_select' is on so the following happens:
   *  - uses the new join functionality to link the join table to the target table
   *  - creates a join condition based on the keys on both sides
	 *  - restricts the selected data to the one side of the join
	 *  - returns a WaxModelAssociation of the results
	 * However, this can be turned off and used in the old manner (more suited if lots of data; ie moves load from db to application)
	 *  - finds all records in join table
	 *  - creates a set of filters based on these results
	 *  - return the WaxModelAssociation of matching values on the target table
	 *
	 * The reason for having two methods is that the join version removes an all call, saving one more db query, but the join sql
	 * can be slow on large amounts of data, so the other method is here for that purpose.
   */  
  public function get() {
		$links = new $this->target_model;
		if($this->use_join_select){
			//link the table
			$conditions = "(".$links->table.".".$links->primary_key."=".$this->join_model->table.".".$this->join_field($links) ." AND ";
			$conditions.= $this->join_model->table.".".$this->join_field($this->model)."=".$this->model->primval .")";
			$this->join_model->select_columns = array($links->table.".*");
			$res = $this->join_model->clear()->left_join($links->table)->join_condition($conditions)->filter($links->table.".".$links->primary_key . " > 0");
			return new WaxModelAssociation($res, $this->model, $this->field);
		}else{
			$vals = $this->join_model->all();
    	$links = new $this->target_model;
    	if(!$vals->count()){
      	//filter added that never evaluates since we want none of the target model's to return
      	$links->filter("1 = 2");
      	return new WaxRecordset($links, array());
    	}	
    	foreach($vals as $val) $filters[]= $links->primary_key."=".$val->{$this->join_field($links)};
    	return new WaxModelAssociation($links->filter("(".join(" OR ", $filters).")"), $this->model, $this->field);
		}
  }
  
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
    
  }
  
  public function unlink($model) {
    $links = new $this->target_model;
    
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
  
	//clean up the joins
	public function delete(){
		//delete join tables!
		$data = $this->model->{$this->field};
		if($data->count()) $this->unlink($data);
	}
	
  public function save() {
    return true;
  }

  protected function join_field(WaxModel $model) {
    return $model->table."_".$model->primary_key;
  }

  public function get_choices() {
    if($this->model->identifier) {
      $this->choices[""]="Select";
      foreach($j->all() as $row) $this->choices[$row->{$row->primary_key}]=$row->{$row->identifier};
    }
    return $this->choices;
  }

  public function __call($method, $args) {
    $vals = $this->join_model->all();
    $links = new $this->target_model;
    if(!$vals->count()) return new WaxRecordset($this->model);
    foreach($vals as $val) $filters[]= $links->primary_key."=".$val->{$this->join_field($links)};
    $links->filter("(".join(" OR ", $filters).")");

    return call_user_func_array(array($links, $method), $args);
  }

} 
