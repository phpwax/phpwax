#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/../app/config/environment.php';

if(is_dir(WAX_ROOT."wax")) {
	echo "Wax is already in your app directory. Delete it to freeze a new version";
} elseif(defined("WAX_EDGE")) {
	$command = "svn export svn://svn.webxpress.com/home/SVN/wxframework/trunk ".dirname(__FILE__)."/../wax";
	system($command);
} else {
	$command = "svn export svn://svn.webxpress.com/home/SVN/wxframework/tags/".WAX_VERSION."/ ".dirname(__FILE__)."/../wax";
	system($command);
}

?>