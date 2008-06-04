<?php

/******* development.php runs commands ony in development environment */


/************ Log Levels *****************************/
Config::set("log_info", true);
Config::set("log_warn", true);
Config::set("log_error", true);


/**** Force all emails to be delivered to one email address */
//Config::set("email_intercept", "you@yourdomain.com");