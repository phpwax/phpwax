<?php
class PageController extends ApplicationController
{
	
	public function index() {
		$user = new User;
		if($user->is_posted() && $user->update_attributes($_POST['user'])) {
			$this->redirect_to("/success");
		} else {
			$this->hello = "Form Not Submitted";
		}
	}	
	
	public function success() {
		$this->text = "Success";
	}
	
}