<?php
class WaxModelJoin extends WaxModel {
  
  public $left_field = false;
  
  public function init(WaxModel $left, WaxModel $right) {
    $this->table = $left->table."_".$right->table;
    $fields[] = $left->table."_".$left->primary_key;
    $fields[] = $right->table."_".$right->primary_key;
    asort($fields);
    $this->define($fields[0], "IntegerField");
    $this->define($fields[1], "IntegerField");
    $this->left_field = $fields[0];
    $this->right_field = $fields[1];
  }
  
}