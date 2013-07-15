<?php
/**
 * Model with tree handling capabilities
 *
 * @package PHP-Wax
 * @author Sheldon Els & Charles Marshall
 *
 **/
class WaxTreeModel extends WaxModel {
  static public $all_rows;
  public $parent_column;
  public $parent_join_field;
  public $join_order = false;
  public $children_column;
  public $root_path = false;
  public $level = false;
  public $tree_array = false;

  function __construct($params=null) {
    parent::__construct($params);
    if(!$this->parent_column) $this->parent_column = "parent";
    if(!$this->children_column) $this->children_column = "children";
    if(!$this->parent_join_field) $this->parent_join_field = $this->parent_column."_".$this->primary_key;
    $this->define($this->parent_column, "ForeignKey", array("col_name" => $this->parent_join_field, "target_model" => get_class($this)));
    $this->define($this->children_column, "HasManyField", array("target_model" => get_class($this), "join_field" => $this->parent_join_field, "eager_loading" => true));
    $this->tree_setup();
  }

  public function has_children($scope=false){
    $class = get_class($this);
    $model = new $class($scope);
    if($model->filter($this->parent_join_field, $this->primval)->first()) return true;
    return false;
  }

  public function enable_has_child_query(){
    $parent_col = $this->parent_join_field;
    $primary = $this->primary_key;
    $this->select_columns = "
      $this->table.*,
      (SELECT count($primary) FROM `$this->table` as sub_content where `sub_content`.$parent_col= `$this->table`.$primary) as 'has_children'
    ";
    return $this;
  }

  /**
   * function to get the tree structure for in-order traversal via a foreach($model->tree() as $node) type use
   * if the current model is empty it will return the entire tree including all root nodes
   * if the current model is a particular node (has an id) it will only return the tree underneath that node
   * if filters are set on the model, it will return only rows which match those filters (BE WARE, THIS CAN HAVE SOME UNUSUAL RESULTS)
   *
   * @return
   */
  public function tree(){
    $model_class = get_class($this);
    $new_tree = $this->build_tree();
    $this->cached_tree_set(serialize($new_tree));
    return new RecursiveIteratorIterator(new WaxTreeRecordset($this, $new_tree), RecursiveIteratorIterator::SELF_FIRST );
  }

  public function build_tree() {
    $lookup = array();
    $cutoff = $this->primval;
    $model = clone $this;
    foreach( $model->rows() as $item ) {
      $item['children'] = array();
      $lookup[$item['id']] = $item;
    }
    $tree = array();
    foreach( $lookup as $id => $foo ){
      $item = &$lookup[$id];
      if($this->join_order && isset( $lookup[$item[$this->parent_join_field]] )) $lookup[$item[$this->parent_join_field]]['children'][$item[$this->join_order]] = &$item;
      elseif( isset( $lookup[$item[$this->parent_join_field]] ) ) $lookup[$item[$this->parent_join_field]]['children'][] = &$item;
      else $tree[$id] = &$item;
      if($cutoff == $id) $cutoff = array($id => &$item);
    }
    $this->recursive_tree_sort($tree);
    if($cutoff) $tree = $cutoff;
    return array_values($tree);
  }

  public function recursive_tree_sort(&$tree) {
    foreach($tree as &$it){
      if(count($it["children"])){
        ksort($it["children"]);
        $it["children"]=array_values($it["children"]);
        $this->recursive_tree_sort($it["children"]);
      }
    }
  }

  protected function cache_object(){
    $ident = $this->table;
    if($this->filters) $ident .= ":".md5(serialize($this->filters));
    $ident .= ".tree.cache";
    $cache = new WaxCacheLoader("File", CACHE_DIR."/tree/");
    $cache->identifier = CACHE_DIR."/tree/".$ident;
    return $cache;
  }
  protected function cached_tree_get() {
    $cache = $this->cache_object();
    return $cache->get();
  }

  protected function cached_tree_set($value) {
    $cache = $this->cache_object();
    $cache->set($value);
  }

  //clear the cache of the tree
  public function delete() {
    $cache = $this->cache_object();
    $cache->expire();
    return parent::delete();
  }

  public function save(){
    $cache = $this->cache_object();
    $cache->expire();
    return parent::save();
  }

  /**
   * get the root nodes
   * now with caching! yey!
   * @return WaxRecordSet of all the self-parented nodes or nodes with unidentifiable parents
   */
  public function roots() {
    if($root_return = WaxModel::get_cache($this->table, "parent", "rootnodes")) return $root_return;

    /** Methods of finding a root node **/
    //First method: parent reference same as primary key
    $filter[] = "$this->table.{$this->parent_column}_{$this->primary_key} = ".$this->table.".{$this->primary_key}";
    //Second method: parent references a non-existant node (including 0)
    $filter[] = $this->table.".{$this->parent_column}_{$this->primary_key} NOT IN (SELECT {$this->primary_key} FROM `{$this->table}`)";
    //Third method: parent references a nothing
    $filter[] = $this->table.".{$this->parent_column}_{$this->primary_key} IS NULL OR ".$this->table.".{$this->parent_column}_{$this->primary_key} = 0";

    $root_return = $this->filter("(".join(" OR ", $filter).")")->all();

    if($root_return) return $root_return;
    else return false;
  }

  /**
   * this makes an array based on the path from this object back up to its root
   * @return array $paths
   */
  public function path_to_root(){
    if($this->root_path) return $this->root_path;
    //loop up the parent calls and add to the array
    $parent_field = $this->parent_column;
    $path_to_root = array($this->primval);
    $parent_model = $this;
    while($parent_model = $parent_model->$parent_field) $path_to_root[] = $parent_model->row;
    return $this->root_path = new WaxRecordset($this, $path_to_root);
  }

  public function path_from_root(){
    return new WaxRecordset(clone $this, array_reverse((array)$this->path_to_root()->rowset));
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

  public function clear(){
    parent::clear();
    $this->root_path = false;
    $this->level = false;
    $this->tree_array = false;
    return $this;
  }

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
  public function is_root() {
    $parent = $this->{$this->parent_column};
    if(!$parent) return true;
    return false;
  }


  public function root() {
    if($this->is_root()) return $this;
    $parent = $this->{$this->parent_column};
    $return = $parent;
    while($parent && $parent->primval() > 0) {
      $return = $parent;
      $parent = $parent->{$this->parent_column};
    }
    return $return;
  }

  public function siblings() {
    $class=get_class($this);
    $tree = new $class;
    return $tree->filter(array($this->parent_join_field=>$this->{$this->parent_join_field}, $this->primary_key." NOT"=>array($this->primval())))->all();
  }

  public function before_save(){
    //if($this->primval) foreach($this->tree() as $node) if($this->{$this->parent_join_field} == $node->id) throw new WaxException("Tree node cannot have parent in its own subtree.","Application Error");
    $cyclic = false;
    //$path = $this->path_to_root();
    $class= get_class($this);
    $path_to_root = array();
    $col = $this->parent_join_field;
    $pm = new $class($this->$col);

    while($pm->$col > 0){
      $path_to_root[] = $pm->primval ;
      if(in_array($pm->$col, $path_to_root) ){
        $cyclic = true;
        break;
      }
      $pm = new $class($pm->$col);
    }

    if(in_array($this->primval, $path_to_root) || $cyclic) throw new WaxException("Tree node cannot have parent in its own subtree.","Application Error");


  }

  public function syncdb(){
    if(get_class($this) == "WaxTreeModel") return;
    parent::syncdb();
  }

  public function tree_setup(){}


}
?>
