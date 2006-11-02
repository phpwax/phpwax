<?php
/**
 *
 * @package wx.framework.core
 * @author Ross Riley
 **/
class WXTemplate
{
	public $layout_path = null;
	public $view_path;
	public $plugin_view_path=null;
  public $content_for_layout;
	public $layout_content;
	public $view_content;
	public $preserve_buffer = null;
	public $view_base = null;
	public $shared_dir = null;
	
	public function __construct($preserve_buffer = null) {
		if($preserve_buffer) {
			$this->preserve_buffer = true;
		}
	}
	
	public function parse( $view_file ) {
	  $raw_view = substr(strrchr($view_file, "/"),1);
		$this->preserve_buffer ? $buffer = ob_get_clean() : ob_clean();
		ob_start();
		switch(true) {
		  case is_readable(VIEW_DIR.$view_file): $view_file = VIEW_DIR.$view_file; break;
		  case $this->view_base && is_readable($this->view_base.$view_file): $view_file = $this->view_base.$view_file; break;
		  case $this->plugin_view_path && is_readable($this->view_base.$this->plugin_view_path): 
		    $view_file = $this->view_base.$this->plugin_view_path; break;
		  case $this->shared_dir && is_readable($this->shared_dir.$raw_view): $view_file = $this->shared_dir.$raw_view; break;
		  default: $view_file = VIEW_DIR.$view_file;
		}
		extract((array)$this);
		echo $view_file;
		if(!is_readable($view_file)) {
			throw new WXException("Unable to find ".$view_file, "Missing Template File");
		}
		if(!include($view_file) ) {
			throw new WXUserException("PHP parse error in $view_file");
		}
		if($this->preserve_buffer) {
			$content = ob_get_clean();
			ob_start();
			echo $buffer;
			return $content;
		} else {
		  return ob_get_clean();
		}
	}
	
	public function setTemplate($file) {
		$this->outer_template=$file;
	}
	
	public function execute() {
		$this->content_for_layout = $this->parse($this->view_path);		
		$this->layout_content = $this->content_for_layout;
		if($this->layout_path) {
			return $this->parse($this->layout_path);
		} else {
			return $this->layout_content;
		}
		
	}
	
  

}
?>