<?php

/**
 * Paginated Recordset class
 *
 * @package PhpWax
 **/


class WaxPaginatedRecordset extends WaxRecordset {
  
  public $current_page=1;
  public $total_pages=false;
  public $per_page=false;
  public $count=false;

	public function __construct(WaxModel $model, $page, $per_page) {
		$this->per_page = $per_page;
		$this->current_page = $page;
		//setup model 
    $this->model = $model;	
		$this->model->offset = (($page-1) * $per_page);
		$this->model->limit = $per_page;
		//paginate the model
		$rowset = $this->paginate($model);
		$this->set_count($model->total_without_limits);
		parent::__construct($model, $rowset);
  }

	public function paginate(WaxModel $model){
		$rows = $model->rows();
		foreach($rows as $row) {
		  $ids[]=$row->$model->primary_key;
		}
		print_r($ids); exit;
		return $ids;
	}
	
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