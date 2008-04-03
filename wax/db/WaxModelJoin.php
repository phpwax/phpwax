<?php
class WaxModelJoin extends WaxModel {
  
  public $left_field = false;
  public $right_field = false;
  public $table = false;
  
  public function __construct(WaxModel $left, WaxModel $right) {
    $this->db = self::$adapter;
    echo "Defining table {$left->table}_{$right->table}"."\n";
    $this->table = $left->table."_".$right->table;
    $this->define($left->table."_".$left->primary_key, "IntegerField");
    $this->define($right->table."_".$right->primary_key, "IntegerField");
    $this->left_field = $left->table;
    $this->right_field = $right->table;
 		$this->define($this->primary_key, $this->primary_type, $this->primary_options);
  }
  
}