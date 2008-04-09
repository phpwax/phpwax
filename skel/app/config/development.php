<?php

/******* development.php runs commands ony in development environment */


/************ Log Levels *****************************/
WaxLog::log("application");
WaxLog::log("db");
WaxLog::log("sql");


/**** Force all emails to be delivered to one email address */
//WXEmail::$email_intercept = "you@yourdomain.com";
