<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WXSearch {

	static public $search_array=array();
	public $search_phrase=false;
	
	
	public function __construct($search_phrase) {
	  $this->search_phrase = $search_phrase;
	}
	
	static public function register_search($key, $table, $field, $order = "") {
	  self::$search_array[]=array("key"=>$key, "table"=>$table, "field"=>$field, "order"=>$order);
	}
	
	static public function unregister_search($key) {
	  for($i=0;$i<=count(self::$search_array); $i++) {
	    if(self::$search_array[$i]['key'] == $key) unset(self::$search_array[$i]);
	  } 
	}
	
	public function get_results() {
	  $setups=array();
	  foreach(self::$search_array as $search) {
	    if(is_array($search['field'])) {
	      try {
  	      WXActiveRecord::getDefaultPDO()->query("ALTER TABLE ".$search['table']." ADD FULLTEXT ".$search['field']." (".implode(",", $search['field']).");");
        } catch(Exception $e) { }
	    } else {
	      try {
  	      WXActiveRecord::getDefaultPDO()->query("ALTER TABLE ".$search['table']." ADD FULLTEXT ".$search['field']." (".$search['field'].");");
        } catch(Exception $e) { }
	    }
	    
	    if(is_array($search['field'])) {
	      $query = "SELECT *, MATCH(";
	      $query .= implode(",",$search['field']);
	      $query .= ") AGAINST('".$this->search_phrase."') AS score FROM ".$search['table'];
	      $query .= " WHERE MATCH(".implode(",", $search['field']).") AGAINST('".$this->search_phrase."')";
	      if($search['order'] != "") {
	        $query .= "ORDER BY ".$search['order'];
        }
	    } else {
	      $query = "SELECT *, MATCH(".$search['field'].") AGAINST('".$this->search_phrase."') AS score FROM ".$search['table'];
	      $query .= " WHERE MATCH(".$search['field'].") AGAINST('".$this->search_phrase."')";
	      if($search['order'] != "") {
	        $query .= "ORDER BY ".$search['order'];
        }
	    }	
	    $model = WXInflections::camelize($search['table'], true);
	    $table = new $model;
      if(is_array($results[$search['key']])) $results[$search['key']] = array_merge($results[$search['key']], $table->find_by_sql($query));
	    else $results[$search['key']]=$table->find_by_sql($query);
	  }
	  return $results;
	}

}
?>