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
    $this->choices = $j->all();
  }
  
  public function make_choices() {
    if(is_array($this->choices)) return $this->choices;
    elseif($this->choices instanceof WaxRecordset) {
      // Grab the first text field to display
      foreach($this->model->columns as $col) {

      }
      foreach($this->choices as $choice) {
        $built_choices[$choice->primval]=$choice->{$choice}
      }
    }
  }

  public function validate() {
    return true;
  }
  
  public function get() {
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

  public function __call($method, $args) {
    $vals = $this->join_model->all();
    $links = new $this->target_model;
    if(!$vals->count()) return new WaxRecordset($this->model);
    foreach($vals as $val) $filters[]= $links->primary_key."=".$val->{$this->join_field($links)};
    $links->filter("(".join(" OR ", $filters).")");

    return call_user_func_array(array($links, $method), $args);
  }

} 
