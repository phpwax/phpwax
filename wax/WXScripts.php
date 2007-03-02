<?php

class WXScripts {
  
  public $output=array();
  public $plugin_array = array(
    "cms"=>"svn://php-wax.com/home/phpwax/svn/plugins/cms/trunk",
    "cmscore"=>"svn://php-wax.com/home/phpwax/svn/plugins/cms/trunk",
    "cms-email"=>"svn://php-wax.com/home/phpwax/svn/plugins/subscription_manager/trunk",
    "cms-ecom"=>"svn://php-wax.com/home/phpwax/svn/plugins/cms-ecom/trunk"
  );
  
  
  
  
  public function __construct($type, $argv) {
    ob_end_clean();
    $this->$type($argv);
  }
  
  protected function app_setup() {
    if(!defined("ENV")) define("ENV", "development");
    Autoloader::run_application(ENV, false);
  }
  
  public function controller($argv) {
    $gen = new WXGenerator("controller", array_slice($argv, 1));
  }
  
  public function email($argv) {
    $gen = new WXGenerator("email", array_slice($argv, 1));
  }
  
  public function test($argv) {
    $this->app_setup();
    $gen = new WXGenerator("test", array_slice($argv, 1));
  }
  
  public function docs($argv) {
    $output_dir = WAX_ROOT."doc";
    $source_dir1 = WAX_ROOT."app";
    $source_dir2 = FRAMEWORK_DIR;
    $command = "phpdoc -t {$output_dir} -d {$source_dir1},{$source_dir2} -o HTML:frames:phpedit";
    system($command);
    $this->add_output("Documentation has been created in /doc. Open the index.html file to view.");
  }
  
  public function model($argv) {
    $this->app_setup();
    $gen = new WXGenerator("model", array_slice($argv, 1));
  }
  
  public function migration($argv) {
    $this->app_setup();
    $gen = new WXGenerator("migration", array_slice($argv, 1));
  }
  
  public function plugin($argv) {
    if(!isset($argv[1]) ) {
      $this->add_output("[ERROR] You must supply a plugin name that you wish to install.");
      $this->add_output("Type 'script/plugin_install list' to see available plugins.");
      return false;
    }
    
    if($argv[1]=="list") {
      $this->add_output("The following plugins are available");
      foreach($this->plugin_array as $plugin=>$location) {
        $this->add_output($plugin);
      }
      return false;
    }
    
    if(!array_key_exists($argv[1], $this->plugin_array)) {
      $this->add_output("[ERROR] Plugin not found.");
      $this->add_output("Type 'script/plugin_install list' to see available plugins.");
      return false;
    }
    $source = $this->plugin_array[$argv[1]];
    $output_dir = WAX_ROOT."plugins/".$argv[1]."/";
    echo "This will overwrite files inside the plugin/{$argv[1]} directory. Do you want to continue?: [y/n] ";
    $answer = trim(fgets(STDIN));
    if($answer != "y" && $answer != "yes") {
     $this->add_output("");
     return false; 
    }
    
    $command = "svn export -q {$source} {$output_dir} --force";
    system($command);

    echo("Plugin installed in /plugins/{$argv[1]}."."\n");
    if(is_readable(WAX_ROOT."plugins/".$argv[1]."/installer")) {
      echo "Would you like to run the additional installer script?"."\n".
      "(this may overwrite files inside your app and public folders): [y/n] "; 
      $answer = trim(fgets(STDIN));
      if($answer != "y" && $answer != "yes") {
        $this->add_output("Installer script was skipped. (Just re-run the install if this was a mistake.)"."\n"); 
        return false;
      }
      if(include(PLUGIN_DIR.$argv[1]."/installer"))
        $this->add_output("");
        $this->add_output("Plugin installer successfully ran.");
    } else {
      $this->add_output("");
    }
  }
  
  public function run_tests($argv) {
    error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
    define("ENV", "test");
    $this->app_setup();
    define("CLI_ENV", true);
    if((!include 'simpletest/unit_tester.php') 
      || (!include 'simpletest/reporter.php') 
      || (!include 'simpletest/web_tester.php')
      || (!include 'simpletest/mock_objects.php')
      ) {
      throw new WXDependencyException("Simpletest library required. Install it somewhere in the include path", "Simpletest Dependency Failure");
    }

    if($argv[1] == "wax") {
      $testdir = FRAMEWORK_DIR."/tests";
    } elseif($argv[1] && is_dir(PLUGIN_DIR.$argv[1]."/tests")) {
      $testdir = PLUGIN_DIR.$argv[1]."/tests";
    } else {
      $testdir = APP_DIR."tests";
    }
    AutoLoader::include_dir($testdir);
    $test = new GroupTest('All tests');
    foreach(scandir($testdir) as $file) {  	 
      if(substr($file, -3)=="php" && substr($file,0,1)!=".") { 
        $class = substr($file, 0,-4);	
        $test->addTestClass($class); 	 
      } 	 
    }
    if(TextReporter::inCli()) {
      exit( $test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
  }
  
  public function freeze($argv) {
    if(is_dir(WAX_ROOT."wax")) {
    	$this->add_output("[ERROR] Wax is already in your app directory. Delete it to freeze a new version.");
    	return false;
    } elseif(defined("WAX_EDGE")) {
    	$command = "svn export svn://php-wax.com/home/phpwax/svn/main/trunk/wax/ ".dirname(__FILE__)."/../wax";
    	system($command);
    } else {
    	$command = "svn export svn://php-wax.com/home/phpwax/svn/main/tags/".WAX_VERSION."/wax/ ".dirname(__FILE__)."/../wax";
    	system($command);
    }
  }
  
  public function migrate($argv) {
    if(isset($argv[1]) && $argv[1]=="test" || $argv[1] == "production") {
      define("ENV", $argv[1]);
    	unset($argv[1]);
    } elseif(isset($argv[1]) && $argv[1] =="directory" && isset($argv[2])) {
      $migrate = new WXMigrate(true);
      $direction = "up";
      if(isset($argv[3])) $direction="down";
      $result = $migrate->version_less_migrate($argv[2], $direction, true); 
      exit($result."\n");
    } 

    $this->app_setup();
  	
    $dbdir = WAX_ROOT.'app/db/migrate/';
    if(!is_dir($dbdir)) {
      $command = "mkdir -p $dbdir";
      system($command);
    }
    $version = false;
    if(isset($argv[1])) {
      $version = $argv[1];
    }

    $migrate = new WXMigrate;
    if($version == 'version') {
      $this->add_output("Now at version ".$migrate->get_version());
      return false;
    }
    if($version == 'clean') {
      $result = $migrate->migrate_revert($dbdir);
      $this->add_output("Database reset to version ".$result);
      return false;
    }
    $result = $migrate->migrate($dbdir, $version);
    if($result===false) {
      $this->add_output("No Files to migrate");
      return false;
    }
    $this->add_output("-------------------");
    $this->add_output("Successfully migrated to version ".$result);
    $this->add_output("-------------------");
  }
  
  public function deploy($argv) {
    $deployment_settings = WXConfiguration::get('deploy');
    $remote = new WXRemote($deployment_settings['user'], $deployment_settings['server']);
    if(is_array($deployment_settings['before_deploy'])) {
      foreach($deployment_settings['before_deploy'] as $before) $remote->add_command($before);
    }
    $remote->svn_export($deployment_settings['svn_path'], $deployment_settings['server_path'], 
      $deployment_settings['svn_user'], $deployment_settings['svn_pass']);
    if(is_array($deployment_settings['after_deploy'])) {
      foreach($deployment_settings['after_deploy'] as $after) $remote->add_command($after);
    }
    $remote->run_commands();
    $this->add_output("Application successfully deployed to ".$deployment_settings['server']);
  }
  
  
  protected function add_output($output) {
    $this->output[]=$output;
  }
  
  public function __destruct() {
    foreach($this->output as $out) {
      echo $out."\n";
    }
  }
  
}

?>