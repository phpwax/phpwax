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
	
	public function __construct($preserve_buffer = null) {
		if($preserve_buffer) {
			$this->preserve_buffer = true;
		}
	}
	
	public function parse( $pFile ) {
		$this->preserve_buffer ? $buffer = ob_get_clean() : ob_clean();
		ob_start();
		if(is_readable(VIEW_DIR.$pFile)) {
			$pFile = VIEW_DIR.$pFile;
		} elseif($this->view_base && is_readable($this->view_base.$pFile)) {
			$pFile = $this->view_base.$pFile;
		} elseif($this->plugin_view_path) {
		  $pFile = $this->view_base.$this->plugin_view_path;
		  echo "trying to find ".$pFile; exit;
		} else {
			$pFile = VIEW_DIR.$pFile;
		}
		extract((array)$this);
		if(!is_readable($pFile)) {
			throw new WXException("Unable to find ".$pFile, "Missing Template File");
		}		
		if(!include($pFile) ) {
			throw new WXUserException("PHP parse error in $pFile");
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
	
  

} // END class 
?>