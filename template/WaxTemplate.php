<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxTemplate implements Cacheable{
  
  /** interface vars **/
  public $use_cache = true;
  
  
	public static $response_filters = array(
	    'views'=>  array('default'=>array('model'=>'self', 'method'=>'render_view_response_filter')),
		  'layout'=> array('default'=>array('model'=>'self', 'method'=>'render_layout_response_filter')),
		  'partial'=>array('default'=>array('model'=>'self', 'method'=>'render_partial_response_filter'))
	  );

	public $template_paths = array();
	
	
	public function __construct($values = array()) {
    foreach($values as $var=>$val) {
      $this->{$var}=$val;
    }
	}
	
	

	public function add_path($path) {
	  $this->template_paths[]=$path;
	}
	
	/**
	 * default static function to provide a before hook to rendering views 
	 * lets you do things like replacing markdown style code with proper html etc
	 * As these defaults simply return whats passed in then we need to discard the 
	 * current buffer so no duplication of content
	 * @param string $buffer_string 
	 * @return string
	 * @author charles marshall
	 */
	private static function render_view_response_filter($buffer_string, $template = false){		
		return $buffer_string;
	}
	/**
	 * default static function to give a before hook on rendering a layout
	 * again design so you can manipulate the content of the buffer to allow transformations of content etc
	 * @param string $buffer_string 
	 * @return string
	 * @author charles marshall
	 */
	private static function render_layout_response_filter($buffer_string, $template = false){
		return $buffer_string;
	}
	/**
	 * default static function to give a before hook on rendering a partial
	 * again design so you can manipulate the content of the buffer to allow transformations of content etc
	 * @param string $buffer_string 
	 * @return string
	 * @author charles marshall
	 */
	private static function render_partial_response_filter($buffer_string, $template = false){
		return $buffer_string;
	}
	/**
	 * loop over the registered response filters and pass off function calls to the model and method specified
	 * gives you ability to manipulate buffer content, send to cache etc
	 * @param string $type 
	 * @return void
	 * @author charles marshall
	 */
	private function response_filter($type){
		$return = ob_get_contents();
		ob_end_clean();
		foreach(self::$response_filters[$type] as $filter){
			$return = call_user_func(array($filter['model'], $filter['method']), $return, $this);
		}
		return $return;
	}
	
	public static function add_response_filter($filter_type, $filter_name, $filter){
		self::$response_filters[$filter_type][$filter_name] = $filter;
	}
	public static function remove_response_filter($filter_type, $filter_name){
		unset(self::$response_filters[$filter_type][$filter_name]);
	}
	
	public function parse($suffix="html", $parse_as="layout") {
	  ob_start();
	  if(!$suffix) $suffix = "html";
	  switch($suffix) {
			case "json": $type="text/javascript";break;
	    case "js": $type="text/javascript";break;
	    default: $type="text/".$suffix; break;
	  }
	  if(!headers_sent()) header("Content-Type: $type; charset=utf-8");
	  
	  /** CACHE **/
	  if($cache_object = $this->cache_enabled($parse_as)){
	    //change the suffix if not html - so .xml files etc cache seperately
	    if($suffix != "html") $cache_object->suffix = $suffix.'.cache';
	    else $cache_object->marker = '<!-- from cache -->';
	  
	    if($this->cached($cache_object, $parse_as) ) return $this->cached($cache_object, $parse_as);	    
    }
    
	  
	  foreach($this->template_paths as $path) {
	    if(is_readable($path.".".$suffix)) {
				$view_file = $path.".".$suffix;
				break;
			}
	  }
		extract((array)$this);
		if(!is_readable($view_file)) throw new WXException("Unable to find ".$this->template_paths[0].".".$suffix, "Missing Template File");
		
		if(!include($view_file)) throw new WXUserException("PHP parse error in $view_file");
		$content = $this->response_filter($parse_as);
		
		if($cache_object && $this->cacheable($cache_object, $type)) $this->cache_set($cache_object, $content);
		return $content;
	}
	
	public function add_values($vals_array=array()) {
	  foreach($vals_array as $var=>$val) $this->{$var}=$val;
	}



  /** INTERFACE METHODS **/
  public function cache_identifier($model){    
    return $model->identifier();
  }
  
  public function cacheable($model, $type){    
    if(!$model->excluded($model->config) && $model->included($model->config)) return true;
    else return false;
  }
	public function cached($model, $type){
	  if(!$this->cacheable($model, $type)) return false;
	  else return $model->get();
	}
  public function cache_set($model, $value){
    $model->set($value);
  }
  public function cache_enabled($type){
    $check = $type."_cache";
    if($this->use_cache && is_array(Config::get($check)) && count(Config::get($check))){      
      $cache_config = Config::get($check);
      $cache_engine = $cache_config['engine'];
      if(isset($cache_config['lifetime'])) $cache_lifetime = $this->cache_config['lifetime'];
      $cache_object = new WaxCacheLoader($cache_engine, CACHE_DIR.$type."/", $cache_lifetime);          
      $cache_object->config = $cache_config;
      $cache_object->identifier = $cache_object->identifier();
      return $cache_object;
    }else return false;
  }

}
