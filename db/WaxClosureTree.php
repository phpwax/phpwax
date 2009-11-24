<?php
/**
 * Model with improved tree handling speed by implementing using a closure table
 *
 * @package PHP-Wax
 * @author Sheldon Els
 *
 **/
class WaxClosureTree extends WaxModel {
  public $closure_table_class = "WaxClosureTable";
  public $closure_table = false;
  
 	function __construct($params=null) {
    parent::__construct($params);
    $this->closure_table = new $this->closure_table_class;
    $this->closure_table->table = $this->table."_closure_table";
  }
  public function parent(){
  }
  /**
   * gets the direct children of the current node
   *
   * @return WaxRecordSet
   */
  public function children(){
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
	}
  /**
   * get the root nodes
   * now with caching! yey!
   * @return WaxRecordSet of all the self-parented nodes or nodes with unidentifiable parents
   */
  public function roots() {
  }

  /**
   * this makes a WaxRecordSet based on the path from this object back up to its root
   * @return WaxRecordSet
   */
  public function path_to_root(){
  }

  /**
   * this makes a WaxRecordSet based on the path from this object's root down to it
   * @return WaxRecordSet
   */
  public function path_from_root(){
  }

  /**
   * returns a numeric representation of this objects depth in the tree
   * 0 is root
   * @return integer
   */
  public function get_level() {
  }

  /**
   * returns true for root nodes and false for everything else
   * @return boolean
   */
  public function is_root() {
    if($this->level === 0) return true;
    else return false;
  }
  
  /**
   * returns the root of the current node
   * @return WaxClosureTree
   */
  public function root() {
    $path_from_root = $this->path_from_root();
    return $path_from_root[0];
  }
  
  /**
   * gets the other nodes that are children of this nodes parent
   * @return WaxRecordSet
   */
  public function siblings() {
  }
  
  /**
   * if it doesn't exist, creates the closure table and makes all existing nodes roots
   *
   * @return void
   */
  public function syncdb(){
    $this->closure_table->syncdb();
  }
  
  public function after_insert(){
    $this->closure_table->clear();
    $this->closure_table->ancestor = $this;
    $this->closure_table->descendant = $this;
    $this->closure_table->save();
  }
}
?>
