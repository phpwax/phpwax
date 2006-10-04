<?php
class PageController extends ApplicationController
{
	
	public function index() {
		$user = new User;
		$this->hello = print_r($user, 1);
		//$this->user = $user->find_first();
		//$this->hello = $this_user->name;
	}	
	
}