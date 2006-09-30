<?php
/**
	*  This file sets up a basic version of the application 
	*  that is suitable for testing purposes.
  *  @package wx.php.core
	*/

/**
 *	Includes necessary files.
 
require_once('AutoLoader.php');

AutoLoader::include_dir(FRAMEWORK_DIR);

$configFile=APP_DIR.'config/config.yml';
$config_array = Spyc::YAMLLoad($configFile);
$config_array=WXRoute::merge_environments($config_array);

WXConfigBase::set_instance();
$conf=new ConfigBase;
$conf->init_db($config_array['db']);
AutoLoader::include_dir(MODEL_DIR);
AutoLoader::include_dir(CONTROLLER_DIR);
*/

?>