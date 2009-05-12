<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class WaxTemplate
{
	public static $response_filters = array(
	    'views'=> array('default'=>array('model'=>'self', 'method'=>'render_view_response_filter')),
		  'layout'=>array('default'=>array('model'=>'self', 'method'=>'render_layout_response_filter'))
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
	private static function render_view_response_filter($buffer_string){		
		ob_end_clean();
		return $buffer_string;
	}
	/**
	 * default static function to give a before hook on rendering a layout
	 * again design so you can manipulate the content of the buffer to allow transformations of content etc
	 * @param string $buffer_string 
	 * @return string
	 * @author charles marshall
	 */
	private static function render_layout_response_filter($buffer_string){
		ob_end_clean();
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
		$return = '';
		foreach(self::$response_filters[$type] as $filter){
			$return .= call_user_func(array($filter['model'], $filter['method']), ob_get_contents());
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
	  switch($suffix) {
			case "json": $type="text/javascript";break;
	    case "js": $type="text/javascript";break;
	    default: $type="text/".$suffix; break;
	  }
	  if(!headers_sent())
	    header("Content-Type: $type; charset=utf-8");
	  foreach($this->template_paths as $path) {
	    if(is_readable($path.".".$suffix)) {
				$view_file = $path.".".$suffix;
				break;
			}
	  }
		extract((array)$this);
		if(!is_readable($view_file)) {
			throw new WXException("Unable to find ".$this->template_paths[0].".".$suffix, "Missing Template File");
		}
		if(!include($view_file) ) {
			throw new WXUserException("PHP parse error in $view_file");
		}
		return $this->response_filter($parse_as);
		
	}
	
	public function add_values($vals_array=array()) {
	  foreach($vals_array as $var=>$val) $this->{$var}=$val;
	}


}
