<?php
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