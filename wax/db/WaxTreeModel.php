<?php
/**
 * Model with tree handling capabilities
 *
 * @package PHP-Wax
 * @author Sheldon Els & Charles Marshall
 * 
 **/
class WaxTreeModel extends WaxModel {
  public $parent_column;
  public $children_column;
  public $root_path = false;
  
  function __construct($params=null) {
    parent::__construct($params);
    if(!$this->parent_column) $this->parent_column = "parent";
    if(!$this->children_column) $this->children_column = "children";
    $this->define($this->parent_column, "ForeignKey", array("col_name" => $this->parent_column."_".$this->primary_key, "target_model" => get_class($this)));
    $this->define($this->children_column, "HasManyField", array("target_model" => get_class($this), "join_field" => $this->parent_column."_".$this->primary_key));
  }

  /**
   * get the root nodes
   * now with caching! yey!
   * @return WaxRecordSet of all the self-parented nodes or nodes with unidentifiable parents
   */
  public function get_root() {
  	if($root_return = self::get_cache(get_class($this), "root", "nodes")) return $root_return;

    /** Methods of finding a root node **/
    //First method: parent reference same as primary key
    $filter[] = "{$this->parent_column}_{$this->primary_key} = {$this->primary_key}";
    //Second method: parent references a non-existant node (including 0)
    $filter[] = "{$this->parent_column}_{$this->primary_key} NOT IN (SELECT {$this->primary_key} FROM `{$this->table}`)";
    //Third method: parent references a nothing
    $filter[] = "{$this->parent_column}_{$this->primary_key} IS NULL";

    $root = clone $this;
    $root_return = $root->clear()->filter("(".join(" OR ", $filter).")")->all();

    if($root_return){
      self::set_cache(get_class($this), "root", "nodes", $root_return);
      return $root_return;
    }
  }

  /**
   * this makes an array based on the path from this object back up to its root
   * @return array $path
   */
  public function path_to_root() {
    if($this->root_path) return $this->root_path;
    //get the possible root id's
    foreach($this->roots as $root){
      $rootids[] = $root->primval;
    }
    $current = $this;
    if($current->primval && count($root_ids) > 0){ //sanity check, if this passes an infinite loop can't occur
      while(!in_array($current->primval, $root_ids)){
        $model_name = get_class($this);
        $this->root_path[] = new $model_name($current->primval);
        $current = $current->{$current->parent_column};
      }
      return $this->root_path;
    }
  }
  /**
   * returns a numeric representation of this objects depth in the tree
   * @return integer $level
   */
  public function level() {
    if(!$this->root_path) $this->path_to_root();
    return count($this->root_path) - 1;
  }

}
