<?php
/******* production.php runs commands only in production environment */


/************ Log Levels *****************************/

Config::set("log_info", false);
Config::set("log_warn", false);
Config::set("log_error", true);

/************ Application Error Handling *******************************************************
*
*  When you're running in production mode, you don't want your errors displayed to users.
*  The following commands can be uncommented to handle errors professionally.
*  Firstly the routing redirect_on_error gives a location for a 404 error (page not found)
*  The second redirect_on_error is an application error page.
*  Both of these can be either actions in your application or static pages.
*  
*  Finally email_on_error accepts an email address and email_subject_on_error a text subject.
*  If these are set a copy of the error trace will be emailed to the address. 
*/

WXRoutingException::$redirect_on_error = "/404.html"; // Page not found error

// Application Error and an email address and subject to send details to.
WaxException::$redirect_on_error = "/error.html";
//WaxException::$email_on_error="";
//WaxException::$email_subject_on_error="";
/*********************************************************************************************/


/*********************************************************************************************/

/*********** Your Additional Application Configuration ***************************************
*  This file is run at boot time so if you want to set any systemwide configuration values, 
*  you can do so below this point 
*/
