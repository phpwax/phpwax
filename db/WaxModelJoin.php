<?php
/**
 * The one to one join model
 *
 * @package PHP-WAX
 */
class WaxModelJoin extends WaxModel {
  
  public $disallow_sync = true;
  public $left_field = false;
  public $right_field = false;
  
  
  public function init(WaxModel $model1, WaxModel $model2) {
    if(strnatcmp($model1->table, $model2->table) < 0) {
      $left = $model1;
      $right = $model2;
    } else {
      $left = $model2;
      $right = $model1;
    }
    if(!$this->table) $this->table = $left->table."_".$right->table;
    $this->define($left->table."_".$left->primary_key, "IntegerField");
    $this->define($right->table."_".$right->primary_key, "IntegerField");
    $this->left_field = $left->table."_".$left->primary_key;
    $this->right_field = $right->table."_".$right->primary_key;
  }
  
}