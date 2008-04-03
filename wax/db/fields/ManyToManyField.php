<?php

/**
 * ManyToManyField class
 *
 * @package PHP-Wax
 **/
class ManyToManyField extends WaxModelField {
  
  public $maxlength = "11";
  public $model_name = false;
  public $join_model = false;
  public $hasmany_model = false;
  
  
  public function setup() {
    $this->col_name = false;
    if(!$this->model_name) $this->model_name = Inflections::camelize($this->field, true);
    $j = new $this->model_name;
    if(strnatcmp($this->model->table, $j->table) <0) {
      $left = $this->model;
      $right = $j;
    } else {
      $left = $j;
      $right = $this->model;
    }
    echo $left->table.":::".$right->table."\n";
    $join = new WaxModelJoin($left, $right);
    $join->syncdb();
    $this->join_model = $join->filter(array($this->join_field($this->model) => $this->model->primval));
    $this->hasmany_model = get_class($j);
  }

  public function validate() {
    return true;
  }
  
  public function get() {
    $vals = $this->join_model->all();
    $links = new $this->hasmany_model;
    foreach($vals as $val) $links->filter(array($links->primary_key=>$val->right_field));
    return new WaxModelAssociation($links);
  }
  
  public function set($value) {
    if($value instanceof WaxModel) {
      
    }
    if($value instanceof WaxRecordset) {
      foreach($value as $join) {
        if(!$this->join_model->filter(array($this->join_field($join) => $join->primval) )->all()->count() ) {
          $new = array($this->join_field($join)=>$join->primval, $this->join_field($this->model) => $this->model->primval);
          print_r($new);
          $this->join_model->create($new);
        }
      }
    }
    
  }
  
  public function save() {
    return true;
  }

  protected function join_field(WaxModel $model) {
    return $model->table."_".$model->primary_key;
  }

} 
