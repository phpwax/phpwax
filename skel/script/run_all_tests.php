#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/../app/config/environment.php';
require_once 'wax/AutoLoader.php';
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
AutoLoader::include_dir(APP_DIR."tests");
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
class WXAllTests
{
	public static function main() {
		PHPUnit_TextUI_TestRunner::run(self::suite());
  }

  public static function suite() {
  	$suite = new PHPUnit_Framework_TestSuite('WX App');
		foreach(get_declared_classes() as $classes) {
			if(substr($classes,0,4)=="Test") {
				$suite->addTestSuite($classes);
			}
		}		
		return $suite;
   }
}

WXAllTests::main();
?>