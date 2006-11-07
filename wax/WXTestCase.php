<?php

class WXTestCase extends PHPUnit_Framework_TestCase
{
  public function __construct($args) {
    if (ini_get('error_reporting') != 4095) { 
        error_reporting(E_ALL ^ E_NOTICE); 
    }
    $configFile=APP_DIR.'config/config.yml';
    $config_array = Spyc::YAMLLoad($configFile);
    $config_array['environment']="test";
    $config_array = WXConfigBase::merge_environments($config_array);
    WXConfigBase::set_instance();
    $conf=new WXConfigBase;
    $conf->init_db($config_array['db']);
    AutoLoader::include_dir(MODEL_DIR);
    AutoLoader::include_dir(CONTROLLER_DIR);
    if($args[1] && is_dir(PLUGIN_DIR.$args[1]."/tests")) {
    	$testdir = PLUGIN_DIR.$args[1]."/tests";
    } else {
    	$testdir = APP_DIR."tests";
    }
    self::main($testdir);
  }
  
  public static function main($testdir) {
		PHPUnit_TextUI_TestRunner::run(self::suite($testdir));
  }

  public static function suite($testdir) {
    Autoloader::include_dir($testdir);
  	$suite = new PHPUnit_Framework_TestSuite('WX App');
  	foreach(scandir($testdir) as $file) {
      if(substr($file, -3)=="php" && substr($file,0,1)!=".") {
        $file = substr($file, 0,-4);
        AutoLoader::include_from_registry($file);
        $suite->addTestSuite($file);
      }
    }	
		return $suite;
   }
}
?>