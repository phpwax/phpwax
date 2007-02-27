<?php
/**
 * Performs operations on a remote server.
 * Currently only supports *nix machines, and obviously
 * any commands involved need to be available on the remote machine.
 *
 * SSH must be available from the command line of the local machine.
 *
 * @package PHP-WAX
 * @author Ross Riley
 **/
class WXRemote {

  public $host;
  public $user;
  public $connection;
  public $commands = array();
  
  public function __construct($user, $host) {
    $this->host = $host;
    $this->user = $user;
  }
  
  public function svn_export($svn, $path, $user, $pass) {
    if(strlen($path) <2) $path = "./";
    $command = "svn export $svn $path --force";
    if(strlen($user>1)) $command .= " --username $user";
    if(strlen($pass>1)) $command .= " --password $pass";
    $this->commands[] =  $command;
  }
  
  public function run_commands() {
    foreach($this->commands as $command) {
      $system = "ssh ".$this->user."@".$this->host." $command";
      system(escapeshellarg($system));
    }
  }
  
  public function add_command($command) {
    $this->commands[]=$command;
  }
  

}

?>