<?php
require_once "PHPTAL/Trigger.php";
class MessageTrigger implements PHPTAL_Trigger
{
    public function start($phptalid, $tpl)
    {
    	if(Session::isset_var('errors')) {
				$errors=Session::get('errors');
				$errorhtml="<ul class='user_errors'>";
				foreach($errors as $error) {
					$errorhtml.="<li>$error</li>";
					}
				$errorhtml.="</ul>";
				echo $errorhtml;
			}
			
			if(Session::isset_var('user_messages')) {
				$messages=Session::get('user_messages');
				$messagehtml="<ul class='user_messages'>";
				foreach($messages as $message) {
					$messagehtml.="<li>$message</li>";
					}
				$messagehtml.="</ul>";
				echo $messagehtml;
			}	
				
        return self::SKIPTAG;
    }

    // Invoked after tag execution
    public function end($phptalid, $tpl)
    {
    	Session::unset_var('errors');
			Session::unset_var('user_messages');	
    }

}

?>