#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/../app/config/environment.php';
require_once 'wax/AutoLoader.php';
AutoLoader::include_dir(FRAMEWORK_DIR);
$configFile=APP_DIR.'config/config.yml';
$config_array = Spyc::YAMLLoad($configFile);
$deployment_settings = $config_array['deploy'];

$commandstart = "cd ".WAX_ROOT;
system($commandstart);

$command = "ssh ".$deployment_settings['user']."@".$deployment_settings['server'];
$command .= " && svn export ".$deployment_settings['svn_path']." .".$deployment_settings['server_path']." --force";
if($deployment_settings['svn_user'] && $deployment_settings['svn_pass']) {
  $command .= " --username ".$deployment_settings['svn_user']." --password ".$deployment_settings['svn_pass'];
}
system($command);
?>