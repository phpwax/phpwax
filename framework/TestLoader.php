<?php
/**
	*  This file sets up a basic version of the application 
	*  that is suitable for testing purposes.
  *  @package wx.php.core
	*/

/**
 *	Includes necessary files.
 */
require_once('AutoLoader.php');
require_once('wxphp/lib_extended/simpletest/unit_tester.php');
require_once('wxphp/lib_extended/simpletest/reporter.php');

AutoLoader::include_dir(FRAMEWORK_DIR.'lib_core');

$configFile=APP_DIR.'/config/config.yml';
$config_array = Spyc::YAMLLoad($configFile);
$config_array=ConfigBase::merge_environments($config_array);

ConfigBase::init_db($config_array['db']);
AutoLoader::include_dir(APP_DIR.'model');
AutoLoader::include_dir(APP_DIR.'controller');


?>