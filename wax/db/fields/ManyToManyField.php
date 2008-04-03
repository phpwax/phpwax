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
    $join = new WaxModelJoin;
    $join->init($left, $right);
    $join->syncdb();
    $this->join_model = $join->filter(array($this->join_field($this->model) => $this->model->{$this->model->primary_key}));
    $this->hasmany_model = get_class($j);
  }

  public function validate() {
    return true;
  }
  
  public function get() {
    $vals = $this->join_model->all();
    $links = new $this->hasmany_model;
    foreach($vals as $val) $links->filter(array($links->primary_key=>$val->right_field));
    return $links->all();
  }
  
  public function set($value) {
    if($value instanceof WaxModel) {
      $res = $this->join_model->filter(array($this->join_field($value) => $value->{$value->primary_key} ));
      if(!$res->count()) $this->join_model->create(array($this->join_field($value) => $value->primary_key));
    }
    if($value instanceof WaxRecordset) {
      foreach($value as $join) {
        $res = $this->join_model->filter(array($this->join_field($join) => $join->{$join->primary_key} ));
        if(!$res->count()) $this->join_model->create(array($this->join_field($join) => $join->primary_key));
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
