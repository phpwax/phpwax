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

  public function init(WaxModel $left, WaxModel $right) {
    if(!$this->table) $this->table = $left->table."_".$right->table;
    $this->define($left->table."_".$left->primary_key, (($left->primary_type == "AutoField") ? "IntegerField" : $left->primary_type) );
    $this->define($right->table."_".$right->primary_key, (($right->primary_type == "AutoField") ? "IntegerField" : $right->primary_type) );
    $this->left_field = $left->table."_".$left->primary_key;
    $this->right_field = $right->table."_".$right->primary_key;
  }

}