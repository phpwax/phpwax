<?php
/**
 * This class extends as built in phpwax model called WXEmail (pre release naming convention, will be updated to match new style soon)
 * The WXEmail class handles sending of emails in a nice fashion and extending it allows you to do some nice things.
 * 
 * Firstly, look at app/view/contact to see the actual body of the emails being sent (the file names match the method names just like 
 * controllers and actions). 
 *
 * You will have noticed that to actually send the email you can call send_<name of method> and that will sort it for you. This send prefix
 * just simplifies the controller code.
 *
 * The values you assign to $this can be accessed directly in the respective view (take a look at app/view/contact/contact.txt) 
 */

class Contact extends WXEmail {
  
  public $from = "test@example.com"; //this variable is the from email address
	public $from_name = "Example"; //this is the from name of the sender
	public $contact_to = "test@example.com"; //this is an extra variable used in this model to store who receives the email
	
	public function contact_form($data){
		/* the to of the email is set to match the extra email above, this is done so we can send other emails to 
		 other people in other methods inside this class
		*/
		$this->to = $this->contact_to;
		$this->subject = "We Have Contact!"; //the email subject
		/*this sets of variables on $this object to the values passed in. It will be used inside the contact 
		 view (ie the email body) and sent*/
    $this->name = $data["name"];
    $this->email = $data["email"];
    $this->telephone = $data["telephone"];
    $this->message = $data["message"];
	} 

  
}

?>