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
  public $level = false;
  
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
  public function roots() {
  	if($root_return = self::get_cache(get_class($this), "parent", "rootnodes")) {
  	  print_r($root_return);
  	  die("OH MY WE HAVE A CACHE")
  	  
  	  return $root_return;
  	  
  	}

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
      self::set_cache(get_class($this), "parent", "rootnodes", $root_return);
      return $root_return;
    }
  }

  /**
   * this makes an array based on the path from this object back up to its root
   * @return array $paths
   */
  public function path_to_root() {
    if($this->root_path) return $this->root_path;
    //get the possible root id's
    foreach($this->roots() as $root){
      $rootids[] = $root->primval;
    }
    $current = clone $this;
    if($current->primval && count($rootids) > 0){ //sanity check, if this passes an infinite loop can't occur
      while(!in_array($current->primval, $rootids)){
        $this->root_path[] = $current;
        $current = $current->{$current->parent_column}; //move up a node
      }
      $this->root_path[] = $current; //loop stops on the root node, so add it into the array
      foreach($this->root_path as $path) $paths[]=$path->{$path->primary_key};
      return $paths;
    }
  }
  /**
   * returns a numeric representation of this objects depth in the tree
   * @return integer $level
   */  
  public function get_level() {
    if($this->level) return $this->level;
    if(!$this->root_path) $this->path_to_root();
    $this->level = count($this->root_path) - 1;
    return $this->level;
  }

}
