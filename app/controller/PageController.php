<?php
class PageController extends ApplicationController
{
  
	function controller_global() {

  }
	
	public function index() {
		$user = new User;
		$this_user = $user->find_first();
		
		$this->hello=$this_user->name;
	}	
	
}