<?php

/**
 * Paginated Recordset class
 *
 * @package PhpWax
 **/


class WaxPaginatedRecordset extends WaxRecordset {
  
  public $current_page=1; //default - page 1 (no zeroes)
  public $total_pages=false; //total number of pages (without limits)
  public $per_page=false;  //number records per page
  public $count=false; 

	/**
	 * the constructor takes the model and values passes in, assigns values to internal
	 * vars, sets up the offset and limit on the model and use this->paginate
	 * calls parent __construct
	 * @param string $WaxModel 
	 * @param string $page 
	 * @param string $per_page 
	 */	
	public function __construct(WaxModel $model, $page, $per_page) {
		$this->per_page = $per_page;
		$this->current_page = $page;
		//setup model 
    $this->model = $model;	
		$this->model->_offset = (($page-1) * $per_page);
		$this->model->_limit = $per_page;
		//paginate the model
		$rowset = $model->rows();
		$this->set_count($model->total_without_limits);
		parent::__construct($model, $rowset);
  }
	
	//this here for backwards compatibility
	public function eager_load(){ return $this; }
	
	/**
	 * use the count value passed in to work out total number of pages
	 * @param string $count 
	 */	
  public function set_count($count) {
    $this->count = $count;
    $this->total_pages = ceil($count / $this->per_page);
  }
  
  public function next_page() { return $this->current_page +1;}
  public function previous_page() { return $this->current_page -1;}
  
  public function is_last($page) {
    if($page==$this->total_pages) return true;
    return false;
  }
  public function is_first() {
    if($this->current_page==1) return true;
    return false;
  }
  public function is_current($page) {
    if($this->current_page==$page) return true;
    return false;
  }
}