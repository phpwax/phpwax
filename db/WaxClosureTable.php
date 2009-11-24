<?php
/**
 * Model used to store closure table for WaxClosureTree
 *
 * @package PHP-Wax
 *
 **/
class WaxClosureTable extends WaxModel {
  public function setup(){
    $this->define("ancestor","ForeignKey",array("col_name"=>"ancestor","target_model"=>"WaxClosureTree"));
    $this->define("descendant","ForeignKey",array("col_name"=>"descendant","target_model"=>"WaxClosureTree"));
    $this->define("level","IntegerField");
  }
}
?>