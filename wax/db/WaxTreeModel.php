<?php

/**
 * Model with tree handling capabilities
 *
 * @package PHP-Wax
 * @author Sheldon Els
 * 
 **/
class WaxTreeModel extends WaxModel {
  public $parent_column;
  public $children_column;
	public $root_node = false;
	public $tree_array = false;
	public $root_path = false;
	public $level = false;
	
  
 	function __construct($params=null) {
 	  parent::__construct($params);
 	  if(!$this->parent_column) $this->parent_column = "parent";
 	  if(!$this->children_column) $this->children_column = "children";
    $this->define($this->parent_column, "ForeignKey", array("col_name" => $this->parent_column."_".$this->primary_key, "target_model" => get_class($this)));
    $this->define($this->children_column, "HasManyField", array("target_model" => get_class($this), "join_field" => $this->parent_column."_".$this->primary_key));
  }
  
  
  public function save() {
    $return_val = parent::save();
    if(!$return_val->{$return_val->parent_column}){
      $return_val->{$return_val->parent_column} = $return_val->root();
    }
    return $return_val;
  }
  
  public function syncdb() {
    $res = parent::syncdb();
    if(!$this->root()){
      $class_name = get_class($this);
      $root = new $class_name;
      foreach($root->columns as $col_name => $column){
        $col = $root->get_col($col_name);
        if(!$col->blank){
          if($col->default){
            $root->$col_name = $col->default;
          }else{
            if($col instanceof CharField || is_subclass_of($col, "CharField"))
              $root->$col_name ="";
            else
              $root->$col_name = 0;
          }
        }
      }
      $root = $root->save();
      $root->{$this->parent_column} = $root;
    }
    return $res;
  }

	/** tree generation **/
  public function get_root() {
		if($this->root_node) return $this->root_node;
    $root = clone $this;
    $root_return = $root->clear()->filter($this->get_col($this->parent_column)->col_name . " = $this->primary_key")->first();
    //legacy support code
    if(!$root_return) $root_return = $root->clear()->filter(array($this->get_col($this->parent_column)->col_name => "0"))->first();    
    $this->root_node = $root_return;
		return $this->root_node;
  }
	
	public function generate_tree($data = false){
		if(!$data) $data = array($this->get_root());

		foreach($data as $node){
			$model_name = Inflections::camelize($this->table, true);
			$children = $node->{$this->children_column};
			$this->tree_array[] = new $model_name($node->{$this->primary_key});
			if($children && $children->count())	$this->generate_tree($children);
		}
	}

  public function path_to_root() {
		if($this->root_path) return $this->root_path;
    if(!$this->root_node->id) $this->get_root();
    if($this->id){
			$parent = $this;
    	while($parent->primval != $this->root_node->primval){
				$model_name = Inflections::camelize($this->table, true);
      	$array_to_root[] = new $model_name($parent->{$this->primary_key});
      	$parent = $parent->{$this->parent_column};
    	}
		}	
    $array_to_root[] = $this->root_node;
    $this->root_path = $array_to_root;
		return $this->root_path;
  }
  
  public function get_level() {
		if($this->level) return $this->level;
		if(!$this->tree) $this->array_to_root();
    $this->level = count($this->tree) - 1;
		return $this->level;
  }

  //not needed with the HasManyField implementation -- leaving code in case it's too slow
  /*public function parent() {
    $parent = clone $this;
    return $parent->clear()->filter($parent->primary_key => $this->{$this->parent_column})->first();
  }
  
  public function children() {
    $children = clone $this;
    return $children->clear()->filter($this->parent_column => $this->primval)->all();
  }*/
  
}
?>