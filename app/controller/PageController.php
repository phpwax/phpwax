<?php
class PageController extends ApplicationController
{
	public function controller_global() {
		//$this->add_spec("user", "");
	}
	
	public function index() {
		$user2 = new User(1);
		$this->hello = print_r($user2->article->find_first()->url, 1);
	}	
	
	public function is_satisfied_by() {
		return "hello";
	}
	
}