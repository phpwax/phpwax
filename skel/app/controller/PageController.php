<?php
class PageController extends ApplicationController
{
	
	public function index() {
		$user = new User;
		if($user->is_posted() && $user->update_attributes($_POST['user'])) {
			$this->redirect_to("/success");
		}
		$this->hello = $this->route_array;
	}	
	
	public function success() {
		$this->text = "Success";
	}
	
}