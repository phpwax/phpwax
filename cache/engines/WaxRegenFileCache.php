<?
class WaxRegenFileCache{

	public function __construct($data_file){
		$config = unserialize(file_get_contents($data_file));
		$post = unserialize($config['post']);
		$url = $this->parse_location($config['location']);
		if(count($post)==0){
			touch($config['lock']);
			if($content = $this->curl($url) ){
			  if(($start = strpos($content, "<") !== false) && ($end = strrpos($content, ">")) $content = substr($content, $start, ($end - $start));
				file_put_contents($config['ident'], $content);
				$config['time'] = time();
				$config['regen'] = date("Y-m-d H:i:s");
				file_put_contents($data_file, serialize($config));
			}
			unlink($config['lock']);
		}
	}

	public function curl($url){
    $headers = array(	"Content-Type: application/html; charset=UTF-8", "Accept: application/html; charset=UTF-8");
		$session = curl_init($url);
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($session, CURLOPT_USERAGENT, API_USER_AGENT);
		$exec =  curl_exec($session);
		$info = curl_getInfo($session);
		if($info['http_code'] == 200) return $exec;
		return false;
  }

	public function parse_location($url){
		$parsed = parse_url($url);
		return $parsed['scheme']."://".$parsed['host'].$parsed['path']."?no-wax-cache=1&".$parsed['query'];
	}
}


if(isset($argv)){
	foreach($argv as $file) if(is_readable($file) && strstr($file, "tmp")) $cache = new WaxRegenFileCache($file);
}

?>