<?php
/**
 *
 * @package wx.framework.core
 * @author Ross Riley
 **/
class WXTemplate
{
	public $layout_path;
	public $view_path;
  public $content_for_layout;
	public $layout_content;
	public $view_content;
	
	public function parse( $pFile ) {
		ob_clean ();
		ob_start();
		$pFile = substr($pFile,0,strrpos($pFile, '/'));
		$pFile = APP_DIR.'view/'.$pFile;
		extract((array)$this);
		include( $pFile );
		return ob_get_clean();
	}
	
	public function parse_no_buffer($pFile) {
		$buffer = ob_get_clean();
		ob_start();
		$pFile = substr($pFile,0,strrpos($pFile, '/'));
		$pFile = APP_DIR.'view/'.$pFile;
		extract((array)$this);
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
		echo $this->parse($this->layout_path);
	}

} // END class 
?>