<?php

/**
 * Page Controller
 *
 * This is a default controller installed by PHP-WAX
 *
 * All you need to do in this controller is make one public method for each url.
 * Then make html templates in the 'view/page' directory.
 *
 * By default it will use the application.html layout (app/view/layouts/) 
 **/

class PageController extends ApplicationController {

	
  /**
   * This is the landing page for the controller. As this is the default controller, and the default action
   * this function is responsible for displaying the content for the home page (ie mydomain.com). In this case 
	 * it just displays the content from the view (app/view/page/index.html)
   */  
  public function index(){}

	/**
	 * this is the about me page referenced in the web site. Notice how the hyphen (-) in the url (about-me)
	 * gets converted to an underscore (_) in the function name. The $this->action variable and 
	 * therefore the 'view' use the hyphen (-). If you take a look inside app/view/page folder you will
	 * see a file named 'about-me.html' 
	 *
	 * This page simply displays some text, so the function is empty.
	 *
	 */		
	public function about_me(){}
	
	
  /**
   * The news page referenced in the web site. As with 'about-me' the function is empty. The content displayed is
   * inside the view (app/view/page/news.html).
   */  
	public function news(){}


	/**
	 * phpwax comes with some very useful tools built in for small sites. This page demonstrates the WaxForm system
	 * which creates forms for you passed on either existing models or (like in this case) you can create custom
	 * fields. This also shows you how to send the results via an email
	 */
	public function contact_me(){
		$this->contact_form = new WaxForm();
	}
	
	
}