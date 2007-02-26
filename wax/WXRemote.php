<?php
/**
 * Performs operations on a remote server.
 * Currently only supports *nix machines, and obviously
 * any commands involved need to be available on the remote machine.
 *
 * 
 *
 * @package PHP-WAX
 * @author Ross Riley
 **/
class WXRemote {

  public $host;
  public $port;
  public $connection;
  
  public function __construct($host, $port) {
    $this->connection = fsockopen($host, $port);
    $output=fgets($this->connection, 512);
  }
  
  public function svn_export($svn, $path=".", $user, $pass) {
    $command = "svn export $svn $path --force --username $user --password $pass";
    $output = fwrite($this->connection, $command);
  }
  
  public function clean_deploy() {
    
  }

}

?>