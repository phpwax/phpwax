<?php
/**
 * The one to one join model
 *
 * @package PHP-WAX
 */
class WaxModelJoin extends WaxModel {
  
  public $left_field = false;
  public $right_field = false;
  
  public function init(WaxModel $left, WaxModel $right) {
    $this->table = $left->table."_".$right->table;
    $this->define($left->table."_".$left->primary_key, "IntegerField");
    $this->define($right->table."_".$right->primary_key, "IntegerField");
    $this->left_field = $left->table."_".$left->primary_key;
    $this->right_field = $right->table."_".$right->primary_key;
  }
  
}