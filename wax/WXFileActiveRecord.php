<?php
/**
 * extension of WXActiveRecord class
 * gives added facilities for storing files in the filesystem
 *
 * @package waxphp
 * @author Ross Riley
 **/
class WXFileActiveRecord extends WXActiveRecord
{
  public $file_base = "public/files/";
  public $url_base = "files/";
  public $max_image_size = 0;
  public $write_image_thumbs=true;
  public $thumb_base = "images/thumbs/";
  public $path_column = "path";
  public $file_column = "filename";
  public $type_column = "type";
  public $create_thumbs = array("thumb"=>"80", "medium"=>"150");
  
  public function __construct($param=null) {
    parent::__construct($param);
    if(!is_dir(WAX_ROOT.$this->file_base)) mkdir(WAX_ROOT.$this->file_base, 0777);
		if(!is_dir(PUBLIC_DIR.$this->thumb_base )) mkdir(PUBLIC_DIR.$this->thumb_base, 0777);
		if(!is_writable(WAX_ROOT.$this->file_base)) throw new WXPermissionsException("Files directory is not writable");
  }
  
  public function save() {
		if(is_array($this->{$this->file_column}) && isset($this->{$this->file_column}['tmp_name'])) {
    	$this->handle_file($this->{$this->file_column});
    	$this->resize_image();
			$new_id = parent::save();
    	$this->write_thumbs($new_id);
			return $new_id;
		} else return parent::save();
  }

  
  public function delete($id) {
    $record = $this->find($id);
		if($record->{$this->file_column}) {
			$file_to_delete = $record->{$this->path_column}.$record->{$this->file_column};
    	if(is_file($file_to_delete) ) unlink($file_to_delete);
    	$this->clear_thumbs($id);
		}
		return parent::delete($id);
  }
  
  protected function handle_file($file) {
    $up_tmp_name = $file['tmp_name'][$this->file_column];
    $new_name = $file['name'][$this->file_column];
		$destination = WAX_ROOT.$this->file_base;
		$this->{$this->path_column} = $destination;
    $this->{$this->type_column} = $file['type'][$this->file_column];
    $this->{$this->file_column} = File::safe_file_save($destination, $new_name);
		$destination = $destination.$this->{$this->file_column};
    if(move_uploaded_file($up_tmp_name, $destination) ) {
 		  chmod($destination, 0777);
    }
  }
    
  protected function resize_image() {
    $original = $this->{$this->path_column}.$this->{$this->file_column};
    if($this->max_image_size >0 && File::is_image($original)) {
  		File::resize_image($original, $original, $this->max_image_size, true);
  		return true;
  	} else return false;
  }
    
  protected function write_thumbs($id) {
    $original = $this->{$this->path_column}.$this->{$this->file_column};
  	if(!$this->write_image_thumbs || !File::is_image($original)) {
  	  return false;
	  }
  	$thumb_dir = PUBLIC_DIR.$this->thumb_base.$id."/";
  	if(!is_dir($thumb_dir)) mkdir($thumb_dir, 0777);
  	foreach($this->create_thumbs as $thumb=>$size) {
  		if(!is_dir($thumb_dir.$thumb)) mkdir($thumb_dir.$thumb, 0777);
  		$destination = $thumb_dir.$thumb."/".$this->{$this->file_column};
  		if(File::is_image($original)) File::resize_image($original, $destination, $size);
  	}
  	return true;
  }
  
  protected function clear_thumbs($id) {
		if(File::recursively_delete($this->thumb_base.$id) ) return true;
		return false;
	}
	
	public function get_root_path() {
    return WAX_ROOT.$this->file_base;
  }
  
  public function list_path($path, $params=null) {
    $method = "find_all_by_".$this->path_column;
    return $this->{$method}($path, $params);
  }
	
	public function file_url() {
		return "/".$this->url_base.$this->{$this->file_column};
	}
	
	public function thumb_url($thumb_name) {
		return "/".$this->thumb_base.$this->id."/$thumb_name/".$this->{$this->file_column};
	}

} 

?>