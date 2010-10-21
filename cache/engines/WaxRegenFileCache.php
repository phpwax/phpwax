<?
class WaxRegenFileCache{

	public function __construct($data_file){
		$config = unserialize(file_get_contents($data_file));
		$post = unserialize($config['post']);
		$url = $this->parse_location($config['location']);
		if(count($post)==0 && !is_file($config['lock'])){
			touch($config['lock']);
			if($content = $this->curl($url) ){
			  $start = strpos($content, "<");
			  if($start !== false) $content = substr($content, $start);
			  $end = strrpos($content, ">")+1;
			  if($end !== false) $content = substr($content, 0, $end);
				file_put_contents($config['ident'], $content);
				$config['time'] = time();
				$config['regen'] = date("Y-m-d H:i:s");
				file_put_contents($data_file, serialize($config));
			}
			if(is_file($config['lock'])) unlink($config['lock']);
		}
	}

	public function curl($url){
    $headers = array(	"Content-Type: application/html; charset=UTF-8", "Accept: application/html; charset=UTF-8");
		$session = curl_init($url);
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_FOLLOWLOCATION, 1);
		$exec =  curl_exec($session);
		$info = curl_getInfo($session);
		if($info['http_code'] == 200) return $exec;
		return false;
  }

	public function parse_location($url){
		$parsed = parse_url($url);
		return $parsed['scheme']."://".$parsed['host'].((isset($parsed['path']))?$parsed['path']:"")."?no-wax-cache=1&".((isset($parsed['query']))?$parsed['query']:"");
	}
}


if(isset($argv)){
	foreach($argv as $file) if(is_readable($file) && !strstr($file, ".php")) $cache = new WaxRegenFileCache($file);
}

?>