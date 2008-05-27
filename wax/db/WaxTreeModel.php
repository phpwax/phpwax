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
  
 	function __construct($params=null) {
 	  parent::__construct($params);
 	  if(!$this->parent_column) $this->parent_column = "parent";
 	  if(!$this->children_column) $this->children_column = "children";
    $this->define($this->parent_column, "ForeignKey", array("col_name" => $this->parent_column."_".$this->primary_key));
    $this->define($this->children_column, "HasManyField", array("model_name" => get_class($this), "join_field" => $this->parent_column."_".$this->primary_key));
  }
  
  public function root() {
    $root = clone $this;
    $root_return = $root->clear()->filter($this->get_col($this->parent_column)->col_name . " = $this->primary_key")->first();
    
    //legacy support code
    if(!$root_return){
      $root_return = $root->clear()->filter(array($this->get_col($this->parent_column)->col_name => "0"))->first();
    }
    
    return $root_return;
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

  public function array_to_root() {
    $root = $this->root();
    $parent = $this;
    while($parent->primval != $root->primval){
      $array_to_root[] = $parent;
      $parent = $parent->{$this->parent_column};
    }
    $array_to_root[] = $root;
    return $array_to_root;
  }
  
  public function get_level() {
    $array_to_root = $this->array_to_root();
    return count($array_to_root) - 1;
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