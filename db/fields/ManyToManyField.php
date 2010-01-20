<?php

/**
 * ManyToManyField class
 *
 * @package PHP-Wax
 **/
class ManyToManyField extends HasManyField {
  
  public $join_model_class = "WaxModelJoin";
	public $join_table = false; //this chap means that you can pass any name for the join table in on define()

  
  

  /**
	 * Reads the load strategy from the setup and delegates either to eager_load or lazy load
	 * @return WaxModelAssociation
	 */	
  public function get($filters = false) {
    $target = new $this->target_model;
    if($this->model->row[$this->field] instanceof WaxModelCollection) return $this->model->row[$this->field];
    if($this->model->pk()) $constraints = array($this->join_field => $this->model->pk());
    return $this->lazy_load($target);
  }

  
  protected function lazy_load($target) {   
    $left_field = $this->model->table."_".$this->model->primary_key;
    $right_field = $target->table."_".$target->primary_key;
    $this->join_model->select_columns=$right_field;
    $ids = array();
    foreach($this->join_model->rows() as $row) $ids[]=$row[$right_field];
    return $this->model->row[$this->field] = $this->create_association($target,$ids);
  }
  
  protected function eager_load() {
    
  }
  
  
	/**
	 * as a save on a many to many doesn't do anything, just return true
	 */
  public function save() {
    return true;
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
  public function before_sync() {}
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



} 
?>