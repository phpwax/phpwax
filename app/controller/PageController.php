<?php
class PageController extends ApplicationController
{
	public function controller_global() {
		//$this->add_spec("user", "");
	}
	
	public function index() {
		$user = new User;
		//$this->hello = print_r($user->query("SELECT * FROM user"), 1);
		//$this->hello = print_r($user->find_all(), 1);
		//$this->hello = print_r($user->find_first(), 1);
		//$this->hello = print_r($user->find_by_sql("SELECT * FROM user"), 1);
		$this->hello = $this->is_satisfied_by();
	}	
	
	public function is_satisfied_by() {
		return "hello";
	}
	
}