<?php
class WaxModelJoin extends WaxModel {
  
  public $left_field = false;
  
  public function init(WaxModel $left, WaxModel $right) {
    $this->table = $left->table."_".$right->table;
    $this->left_field = $left->table."_".$left->primary_key;
    $this->right_field = $right->table."_".$right->primary_key;
    $this->define($this->left_field, "IntegerField");
    $this->define($this->right_field, "IntegerField");
  }
  
}