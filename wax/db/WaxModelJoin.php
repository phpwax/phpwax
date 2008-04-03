<?php
class WaxModelJoin extends WaxModel {
  
  public $left_field = false;
  public $right_field = false;
  
  public function __construct() {
    $this->db = self::$adapter;
 		$this->define($this->primary_key, $this->primary_type, $this->primary_options);
 		$this->setup();
  }
  
  public function init(WaxModel $left, WaxModel $right) {
    $this->table = $left->table."_".$right->table;
    $this->define($left->table."_".$left->primary_key, "IntegerField");
    $this->define($right->table."_".$right->primary_key, "IntegerField");
    $this->left_field = $left->table;
    $this->right_field = $right->table;
  }
  
}