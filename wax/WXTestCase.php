<?php
require "PHPUnit/Framework/TestCase.php";

class WXTestCase extends PHPUnit_Framework_TestCase 
{
	function __construct() {
		require_once 'AutoLoader.php';
		AutoLoader::include_dir(FRAMEWORK_DIR);
		$configFile=APP_DIR.'config/config.yml';
		$config_array = Spyc::YAMLLoad($configFile);
		$config_array['environment']="test";
		$config_array = WXConfigBase::merge_environments($config_array);
		WXConfigBase::set_instance();
		$conf=new WXConfigBase;
		$conf->init_db($config_array['db']);
		AutoLoader::include_dir(MODEL_DIR);
		AutoLoader::include_dir(CONTROLLER_DIR);
	}

}

?>