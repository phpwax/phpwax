<?php
class PageController extends ApplicationController
{
  function controller_global()
  {
    //$this->caches = array("all");
  }
	public function index() {
		$this->hello="hello";
	}
}