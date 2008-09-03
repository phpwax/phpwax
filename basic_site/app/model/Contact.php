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
  
  public $from = "test@example.com";
	public $from_name = "Shave Doctor";
	public $contact_to = "test@example.com";
	
	public function contact($data){
		$this->to = $this->contact_to;
		$this->subject = "We Have Contact!";
    $this->name = $data["name"];
    $this->email = $data["email"];
    $this->telephone = $data["telephone"];
    $this->message = $data["message"];
	} 

  
}

?>