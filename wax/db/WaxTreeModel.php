<?php
/**
 * Model with tree handling capabilities
 *
 * @package PHP-Wax
 * @author Sheldon Els & charles marshall
 * 
 **/
class WaxTreeModel extends WaxModel {
  public $parent_column;
  public $children_column;
    public $root_node = false;
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
   * get the root node, the main way to handle a root node is to have the parent set as itself, but supports a parent id of 0
   * now with caching! yey!
   */
  public function get_root() {
  	if($this->root_node = self::get_cache(get_class($this), "tree", "root")) return $this->root_node;
    if($this->root_node) return $this->root_node;
    $root = clone $this;
    $root_return = $root->clear()->filter($this->parent_column."_".$this->primary_key . " = $this->primary_key")->first();
    //if no root node was found try find one using the old system of a primary key equal to 0
    if(!$root_return) $root_return = $root->clear()->filter(array($this->parent_column."_".$this->primary_key => "0"))->first();
    //if no root node was still found, create one - only if noe exists
    if(!$root_return) $this->create_root();    
    $this->root_node = $root_return;
    self::set_cache(get_class($this), "tree", "root", $this->root_node);
    return $this->root_node;
  }

  /**
   * creates a root node in the database
   */
  private function create_root(){
    $class_name = get_class($this);
    $root = new $class_name;
    //blank out the columns for the new root node
    foreach($root->columns as $col_name => $column){
      $col = $root->get_col($col_name);
      if(!$col->blank){
        if($col->default){
          $root->$col_name = $col->default;
        }else{
          if($col instanceof CharField || is_subclass_of($col, "CharField"))
            $root->$col_name = "";
          else
            $root->$col_name = 0;
        }
      }
    }
    $root = $root->save();
    $root->{$this->parent_column} = $root; //this is the key to the root node, it has a parent of itself
  }

    /**
     * this makes an array based on the path from this object back up to its root
     * @return array $path
     */    
  public function path_to_root() {
        if($this->root_path) return $this->root_path;
    if(!$this->root_node->id) $this->get_root();
    if($this->id){
            $parent = $this;
        while($parent->primval != $this->root_node->primval){
                $model_name = get_class($this);
          $array_to_root[] = new $model_name($parent->primval);
          $parent = $parent->{$this->parent_column};
        }
        }    
    $array_to_root[] = $this->root_node;
    $this->root_path = $array_to_root;
        return $this->root_path;
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
