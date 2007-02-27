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
  
  public function svn_export($svn, $path=".", $user, $pass) {
    $this->commands[] = "svn export $svn $path --force --username $user --password $pass";
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