<?php

/**
 * Paginated Recordset class
 *
 * @package PhpWax
 **/


class WaxPaginatedRecordset extends WXRecordset {
  
  public $current_page=1;
  public $total_pages=false;
  public $per_page=false;
  public $count=false;
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