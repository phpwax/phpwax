<?php
/******* production.php runs commands only in production environment */


/************ Log Levels *****************************/

Config::set("log_info", false);
Config::set("log_warn", false);
Config::set("log_error", true);

