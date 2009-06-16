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
 *
 * Some terminology : 
 *   action = public function / public class method
 *   view = file used to display an action
 *
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
		$this->form = new WaxForm(); //we create the form as $this so it can be accessed inside the views
	  $this->form->add_element("name", "TextInput", array('validate'=>'required')); //this adds a field to the form called name, with the type of textinput
    $this->form->add_element("email", "TextInput", array('validate'=>'email')); //this adds the email field
    $this->form->add_element("telephone", "TextInput"); //telephone field
		/*
		this one is slightly different - a textarea & takes another parameter with html attribute names 
		(as textarea needs those to be valid markup!)
		*/
    $this->form->add_element("message", "TextareaInput", array("cols"=>30, "rows"=>7)); 
		/* 
		 ok, here is the clever form handling...
		 - a call to the save function in this case does not save as there is not a model associated with the form, just custom fields
		 _ the save runs the validation methods for the form and the fields 
		 - if it validates we create a new Contact model (see app/model/Contact.php for details)
		 - the data returned from the save is passed in to the send_contact method (this sends the email - again see model for details)
		 - redirect to a simple thank you page (good practice!)
		*/
    if($data = $this->form->save()) {
      $email = new Contact;
      $email->send_contact_form($data);
      $this->redirect_to("/thanks");
	  }
	}
	
	/**
	 * The new method for creating forms allows for simpler controller / front end code. 
	 * New versions behave in the same fashion as a database model, with a setup call to
	 * specify fields and config - for this example take a look at /app/model/ContactForm.php
	 * Do you think 2 lines of code is small enough??
	 */
	public function new_contact_form(){
	  $this->form = new ContactForm;
	  if($data = $this->form->save()) $this->redirect_to("/thanks/".$data['name']);
	}
	/**
	 * testing multiple forms on the same page with using the same model.
	 * These behave as individual forms, no overlapping, submits for both 
	 */
	 public function multi_contact_form(){
	   $this->form = new ContactForm;
	   /**
	    * we have to over write the form_prefix value while passing in to create a new post namespace to avoid
	    * conflicts.
	    */
	   $this->form2 = new ContactForm(false, false, array('form_prefix'=>'test_form'));
	   //we only redirect when both forms validate but error on either
     if($this->form->save() && $this->form2->save()){  
      $data = $this->form->results();
      $this->redirect_to("/thanks/".$data['name']);
	   } 
     $this->use_view = "new-contact-form";
	 }
	 /**
	  * this shows both forms validating in conjunction; and front end looks like 
	  * a single form
	  */
	 public function multi_contact_form_as_one(){
	   /**
	    * to keep it looking like one form we set form tags false as we'll do that on the front end
	    * and submit false on the first form in order to make sure the validation triggers properly for
	    * the first model and so only one submit tag is shown 
	    */
	   $this->form = new ContactForm(false, false, array('form_tags'=>false, 'submit'=>false));
	   $this->form2 = new ContactForm(false, false, array('form_prefix'=>'test_form', 'form_tags'=>false));
     if($this->form->save() & $this->form2->save()){  //note the single & as we want to validate both forms
      $data = $this->form->results();
      $this->redirect_to("/thanks/".$data['name']);
	   } 
	 }
	
	/**
	 * this is a very simple page that just says thanks on it
	 * used in conjunction with the contact me page
	 */	
	public function thanks(){}
	
	/**
	 * The websites sitemap. This is a little bit smarter then the other actions. Here we introduce the idea of how easy it is to swap
	 * formats in the phpwax framework.
	 * 
	 * The framework checks the url (ie mydomain.com/about-me) for an extension (.html, .xml, .rss, .json etc) and changes the 'format' variable.
	 * The format variable is used when looking for 'views'. For example mydomain.com/about-me.html looks for a file called about-me.html 
	 * where as mydomain.com/about-me.xml would look for a file called about-me.xml
	 *
	 * This mean you do lovely things like providing all of your content in rss format as well as html
	 *
	 * Also means that any logic you want to do, like pulling data from models (see a more advanced site demo), can be done just once
	 *
	 * In this example we see how it is used to create both a html and a google xml sitemap in one 'action'
	 */	
	public function sitemap(){
		//if the format is xml (ie the url used was /sitemap.xml) 
		if($this->use_format == "xml"){
			//then we turn off the main site layout - as we dont want html being used
			$this->use_layout = false;
			//and create a variable for the server base
			$this->base_url = "http://".$_SERVER['HTTP_HOST'];
		}
		
	}
}
?>