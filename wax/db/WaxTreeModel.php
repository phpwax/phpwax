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
    $this->define($this->parent_column, "ForeignKey", array("blank" => false, "col_name" => $this->parent_column."_".$this->primary_key));
    $this->define($this->children_column, "HasManyField", array("model_name" => get_class($this), "join_field" => $this->parent_column."_".$this->primary_key));
  }
  
  public function root() {
    $root = clone $this;
    return $root->clear()->filter(array($this->primary_key => $this->parent_column))->first();
  }
  
  public function save() {
    if(!$this->{$this->parent_column})
      $this->{$this->parent_column} = $this->root();
    return parent::save();
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

  //not needed with the HasManyField implementation -- leaving code in case it's too slow
  /*public function parent() {
    $parent = clone $this;
    return $parent->clear()->filter($parent->primary_key => $this->{$this->parent_column})->first();
  }
  
  public function children() {
    $children = clone $this;
    return $children->clear()->filter($this->parent_column => $this->primval)->all();
  }*/
  
  //will debug later
  /*public function array_to_root() {
    $array_to_root[] = $this;
    $parent = $this->{$this->parent_column};
    while($parent->{$this->parent_column} != $parent){
      $array_to_root[] = $parent;
      $parent = $parent->{$this->parent_column};
    }
    return $array_to_root;
  }
  
  public function get_level() {
    $array_to_root = $this->array_to_root();
    return count($array_to_root) - 1;
  }*/
}
?>