<?php
/**
 *
 * @package php-wax
 * @author Ross Riley
 **/
class WXTemplate
{

	public $template_paths = array();
	
	
	public function __construct($values = array()) {
    foreach($values as $var=>$val) {
      $this->{$var}=$val;
    }
	}

	public function add_path($path) {
	  $this->template_paths[]=$path;
	}
	
	public function parse() {
	  ob_start();
	  echo "<pre>".ob_get_clean()."</pre>"; exit;
	  
	  foreach($this->template_paths as $path) {
	    if(is_readable($path.".html")) {
				$view_file = $path.".html";
				break;
			}
	  }
		extract((array)$this);
		if(!is_readable($view_file)) {
			throw new WXException("Unable to find ".$this->template_paths[0].".html", "Missing Template File");
		}
		if(!include($view_file) ) {
			throw new WXUserException("PHP parse error in $view_file");
		}
		return ob_get_clean();
	}


}
?>