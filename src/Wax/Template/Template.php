<?php
namespace Wax\Template;

/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class Template {
  
  public static $mime_types = array("json" => "text/javascript", 'js'=> 'text/javascript', 'xml'=>'application/xml');
    

	public $template_paths = array();
	
	
	public function __construct($values = array()) {
    foreach($values as $var=>$val) {
      $this->{$var}=$val;
    }
	}
	
	

	public function add_path($path) {
	  $this->template_paths[]=$path;
	}
	
	
	
	public function parse($suffix="html", $parse_as="layout") {
	  ob_start();
	  if(!$suffix) $suffix = "html";
    if(in_array($suffix, array_keys(self::$mime_types))) $type = self::$mime_types[$suffix];
    else $type = "text/".$suffix;
    
	  foreach($this->template_paths as $path) {
	    if(is_readable($path.".".$suffix)) {
				$view_file = $path.".".$suffix;
				break;
			}
	  }
		extract((array)$this);

		if(!is_readable($view_file)) throw new TemplateException("Unable to find ".$this->template_paths[0].".".$suffix, "Missing Template File", print_r($this->template_paths, 1));
		
		if(!include($view_file)) throw new ApplicationException("PHP parse error in $view_file");
		$content = $return = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	public function add_values($vals_array=array()) {
	  foreach($vals_array as $var=>$val) $this->{$var}=$val;
	}



}
