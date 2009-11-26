<?php
/**
 * Model used to store closure table for WaxClosureTree
 *
 * @package PHP-Wax
 *
 **/
class WaxClosureTable extends WaxModel {
  public $closure_tree_class = false;
  
  /**
   * allow class name of closure to be passed in
   */
 	function __construct($params=null,$closure_tree_class=null) {
    $this->closure_tree_class = $closure_tree_class;
    parent::__construct($params);
  }
  
  public function setup(){
    $this->define("ancestor","ForeignKey",array("col_name"=>"ancestor_id","target_model"=>$this->closure_tree_class));
    $this->define("descendant","ForeignKey",array("col_name"=>"descendant_id","target_model"=>$this->closure_tree_class));
    $this->define("depth","IntegerField");
  }

  /**
   * stop sync from syncing base class
   */
  public function syncdb(){
    if($this->table == "wax_closure_table") return;
    parent::syncdb();
  }
}
?>