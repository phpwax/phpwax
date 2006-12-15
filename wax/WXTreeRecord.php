<?php

class WXTreeRecord extends WXActiveRecord implements RecursiveIterator {
  
  public $rel_column="parent_id";
  
  public function has_children() {
    $method = "find_all_by_$this->rel_column";
    return $this->$method($this->id);
  }
  
  public function hasChildren() {return $this->has_children();}
  
  public function has_siblings() {
    $method = "find_all_by_$this->rel_column";
    return $this->$method($this->rel_column);
  }
  
  public function children() {
    $method = "find_all_by_$this->rel_column";
    return $this->$method($this->rel_column);
  }
  
  public function getChildren() {return $this->get_children();}
  
  
  public function siblings() {
    $method = "find_all_by_$this->rel_column";
    return $this->$method($this->{$this->rel_column});
  }
  
  public function is_root() {
    if($this->{$this->rel_column} ==0) return true;
    return false;
  }
  
  public function parent() {
		if($this->{$this->rel_column} ==0) return false;
    return $this->find($this->{$this->rel_column});
  }
  
  public function get_root() {
    if(!$this->is_root()) return false;
 	  $parent = $this->{$this->rel_column};
    while($parent > 0) {
      $record = $this->find($parent);
      $parent = $record->{$this->rel_column}; 
    }
    return $record;
  }
  
  public function create_child($attributes) {
    $class_name = get_class($this);
    $tag = new $class_name;
    $params = array_merge($attributes, array("{$this->rel_column}"=>$this->id));
    $tag->update_attributes($params);
    return $tag;
  }
  
  function get_level() {
 	  $level=0;
 	  if($this->{$this->rel_column} <1) return $level;
 	  $parent = $this->{$this->rel_column};
 	  while($parent > 0) {
 	    $parent = $this->find($parent)->{$this->rel_column};
 	    $level++;
 	  }
 	  return $level;
 	}
  
}

?>