<?php
/**
 *
 * @package PHP-Wax
 * @author charles marshall
 **/
class MaintenanceException extends WXException
{
  //duration - ( (60 * 60) * 24) - 1 day  (86,400)
	function __construct( $message="Servive undergoing maintenance", $status = "503", $duration="86,400") {  	
		$maintenance = Config::get("maintenance");
		$page = PUBLIC_DIR . ltrim($maintenance['redirect'],"/");
		$this->simple_error_log();
		if(is_readable($page)){
			header("HTTP/1.1 ".$status. " ". $message);
			header('Retry-After: '.$duration);
			$content = file_get_contents($page);
      ob_end_clean();
      echo $content;
      exit;
		}			
  }
  
  function simple_error_log() {
    WaxLog::log("error", "[Maintenance] Server undergoing maintenance: {$_GET['route']}");
  }
}


?>