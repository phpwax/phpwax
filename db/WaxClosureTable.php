<?php
class WaxClosureTable extends WaxModel {

 	function init($closure_tree_model) {
    $this->table = $closure_tree_model->table."_closure_table";
    $closure_tree_class = get_class($closure_tree_model);
    $this->define("ancestor","ForeignKey",array("col_name"=>"ancestor_id","target_model"=>$closure_tree_class));
    $this->define("descendant","ForeignKey",array("col_name"=>"descendant_id","target_model"=>$closure_tree_class));
    $this->define("depth","IntegerField");
  }
  
  public function syncdb(){
    if($this->table == "wax_closure_table") return;
    parent::syncdb();
  }
}
?>