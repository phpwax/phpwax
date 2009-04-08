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
  public $tree_array = false;
  
  function __construct($params=null) {
    parent::__construct($params);
    if(!$this->parent_column) $this->parent_column = "parent";
    if(!$this->children_column) $this->children_column = "children";
    $this->define($this->parent_column, "ForeignKey", array("col_name" => $this->parent_column."_".$this->primary_key, "target_model" => get_class($this)));
    $this->define($this->children_column, "HasManyField", array("target_model" => get_class($this), "join_field" => $this->parent_column."_".$this->primary_key, "eager_loading" => true));
  }
  
	public function tree($nodes = false){
		$model_class = get_class($this);
		if($cache_tree = unserialize($this->cached_tree_get() ) ) {
			return new RecursiveIteratorIterator(new WaxTreeRecordset($this, $cache_tree), RecursiveIteratorIterator::SELF_FIRST );
		}else{
			$new_tree = $this->build_tree($this->rows() );
			$this->cached_tree_set(serialize($new_tree));
			return new RecursiveIteratorIterator(new WaxTreeRecordset($this, $new_tree), RecursiveIteratorIterator::SELF_FIRST );
		}
	}
	
	
	public function build_tree($list) {
		$lookup = array();
		foreach( $list as $item ) {
			$item['children'] = array();
			$lookup[$item['id']] = $item;
		}
		$tree = array();
		foreach( $lookup as $id => $foo ){
			$item = &$lookup[$id];
			if( $item['parent_id'] == 0 ) $tree[$id] = &$item;
			elseif( isset( $lookup[$item['parent_id']] ) ) $lookup[$item['parent_id']]['children'][] = &$item;
			else $tree['_orphans_'][$id] = &$item;
		}
		return array_values($tree);
	}

	protected function cached_tree_get() {
		$cache = new WaxCache("section_tree" . ($this->filters) ? (":".md5(serialize($this->filters))) : '');
    if($cache->valid()) return $cache->get();
		else return false;
	}

	protected function cached_tree_set($value) {
		$cache = new WaxCache("section_tree" . ($this->filters) ? (":".md5(serialize($this->filters))) : '');
		$cache->set($value);
	}
	
	//clear the cache of the tree
	public function delete() {	
		$cache = new WaxCache("section_tree");
		$cache->expire();
		return parent::delete();
	}
	
	public function save(){
		$cache = new WaxCache("section_tree");
		$cache->expire();
		return parent::save();		
	}

  /**
   * get the root nodes
   * now with caching! yey!
   * @return WaxRecordSet of all the self-parented nodes or nodes with unidentifiable parents
   */
  public function roots() {
  	if($root_return = WaxModel::get_cache(get_class($this), "parent", "rootnodes")) return $root_return;
  	  
    /** Methods of finding a root node **/
    //First method: parent reference same as primary key
    $filter[] = "{$this->parent_column}_{$this->primary_key} = {$this->primary_key}";
    //Second method: parent references a non-existant node (including 0)
    $filter[] = "{$this->parent_column}_{$this->primary_key} NOT IN (SELECT {$this->primary_key} FROM `{$this->table}`)";
    //Third method: parent references a nothing
    $filter[] = "{$this->parent_column}_{$this->primary_key} IS NULL OR {$this->parent_column}_{$this->primary_key} = 0";

    $root = clone $this;
    $root_return = $root->clear()->filter("(".join(" OR ", $filter).")")->order('id')->all();
    
    if($root_return){
      WaxModel::set_cache(get_class($root), "parent", "rootnodes", $root_return);
      return $root_return;
    }else return false;
  }

  /**
   * this makes an array based on the path from this object back up to its root
   * @return array $paths
   */
  public function path_to_root() {
    if($this->root_path) return $this->root_path;
		$roots = $this->roots();
    //get the possible root id's
    foreach($roots as $root){
			$rootids[] = $root->primval();
		}
    
    $current = clone $this;
    if($current->primval() && count($rootids) > 0){ //sanity check, if this passes an infinite loop can't occur
      while(!in_array($current->primval(), $rootids)){
        $this->root_path[] = $current;
        $current = $current->{$current->parent_column}; //move up a node
      }
      $this->root_path[] = $current; //loop stops on the root node, so add it into the array
      return $this->root_path;
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

  //depreciated functions below this line

  public function cache_whole_tree() {
    $class = get_class($this);
    $all_nodes = $this->all();
    //index the rows by their ids, as well as parents and children ids
    $indexed_rowset = array();
    foreach($all_nodes->rowset as $row){
      if(!$indexed_rowset[$row['id']]['children']) $indexed_rowset[$row['id']]['children'] = array(); //cache empty children arrays too
      $indexed_rowset[$row['id']]['row'] = $row;
      $indexed_rowset[$row['id']]['parent_id'] = $row['parent_id'];
      $indexed_rowset[$row['parent_id']]['children'][] = $row;
    }
    foreach($indexed_rowset as $id => $entry){
      //set parent cache
      $parent = new $class;
      $parent->set_attributes($indexed_rowset[$entry['parent_id']]['row']);
      WaxModel::set_cache($class, $this->parent_column, $id, $parent);
      //set children cache
  		WaxModel::set_cache($class, $this->children_column, $id, $entry['children']);
    }
  }
}
?>