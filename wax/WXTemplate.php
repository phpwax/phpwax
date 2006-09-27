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
  public $content_for_layout;
	public $layout_content;
	public $view_content;
	
	public function parse( $pFile ) {
		ob_clean ();
		ob_start();
		$pFile = VIEW_DIR.$pFile;
		extract((array)$this);
		if(!is_readable($pFile)) {
			throw new WXException("Unable to find ".$pFile, "Missing Template File");
		}
		include( $pFile );
		return ob_get_clean();
	}
	
	public function parse_no_buffer($pFile) {
		$buffer = ob_get_clean();
		ob_start();
		$pFile = VIEW_DIR.$pFile;
		extract((array)$this);
		if(!is_readable($pFile)) {
			throw new WXException("Unable to find ".$pFile, "Missing Template File");
		}
		include( $pFile );
		$content = ob_get_clean();
		ob_start();
		echo $buffer;
		return $content;
	}
	
	public function setTemplate($file) {
		$this->outer_template=$file;
	}
	
	public function execute() {
		$this->content_for_layout = $this->parse($this->view_path);	
		
		$this->layout_content = $this->content_for_layout;
		if($this->layout_path) {
			echo $this->parse($this->layout_path);
		} else {
			echo $this->layout_content;
		}
		
		$wx = new WXCache();
		//$wx->write_to_cache($this->parse($this->layout_path));	
	}
	
  

} // END class 
?>