<?php
/**
 *
 * @package PHP-WAX
 * @author Ross Riley
 **/
class WXSearch {

	static public $search_array=array();
	public $search_phrase=false;
	
	
	public function __construct($search_phrase) {
	  $this->search_phrase = $search_phrase;
	}
	
	static public function register_search($key, $table, $field) {
	  self::$search_array[]=array("key"=>$key, "table"=>$table, "field"=>$field);
	}
	
	static public function unregister_search($key) {
	  for($i=0;$i<=count(self::$search_array); $i++) {
	    if(self::$search_array[$i]['key'] == $key) unset(self::$search_array[$i]);
	  } 
	}
	
	public function get_results() {
	  $setups=array();
	  foreach(self::$search_array as $search) {
	    $setup= "ALTER TABLE ".$search['table']." ADD FULLTEXT (".$search['field'].");";
	    $query = "SELECT *, MATCH(".$search['field'].") AGAINST('".$this->search_phrase."') AS score
	     FROM ".$search['table']." WHERE MATCH(".$search['field'].") AGAINST('".$this->search_phrase."')";
	    $model = WXInflections::camelize($search['table'], true);
	    $table = new $model;
	    WXActiveRecord::getDefaultPDO()->query($setup);
	    echo $query;
	    $results[$search['key']]=$table->find_by_sql($query);
	  }
	  return $results;
	}

}
?>