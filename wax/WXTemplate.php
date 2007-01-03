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
	
	public function __construct($preserve_buffer = false, $plugin = false, $plugin_path =false) {
		if($preserve_buffer) {
			$this->preserve_buffer = true;
		}
		
		if($plugin && $plugin_path) {
		  $this->plugin_view_path = PLUGIN_DIR.$plugin."/view/".$plugin_path;
		  $this->shared_dir = PLUGIN_DIR.$plugin."/view/shared/";
		  $this->view_base = PLUGIN_DIR.$plugin."/view/";
		}
	}
	
	public function parse( $view_file ) {
	  $raw_view = substr(strrchr($view_file, "/"),1);
		$this->preserve_buffer ? $buffer = ob_get_clean() : ob_clean();		
		ob_start();
		switch(true) {
		  case is_readable(VIEW_DIR.$view_file): $view_file = VIEW_DIR.$view_file; break;
		  case is_readable($this->view_base.$view_file): $view_file = $this->view_base.$view_file; break;
		  case is_readable($this->plugin_view_path): $view_file = $this->plugin_view_path; break;
		  case is_readable($this->shared_dir.$raw_view): $view_file = $this->shared_dir.$raw_view; break;
		}
		error_log($view_file);
		extract((array)$this);
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