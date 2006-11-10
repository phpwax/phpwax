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
  
  public function __construct() {
    parent::__construct();
    if($this->url_base) $this->url_path = $this->url_base.$this->{$this->file_column};
  }
    
  public function save() {
    $this->handle_file();
    $this->resize_image();
    $this->write_thumbs();
    parent::save();
  }
  
  public function delete($id) {
    $record = $this->find($id);
    unlink(WAX_ROOT.$this->file_base.$record->{$this->file_column});
    $this->clear_thumbs();
    parent::delete($id);
  }
  
  protected function handle_file() {
    if(is_array($this->{$this->file_column}) && isset($this->{$this->file_column}['tmpname'])) {
      $up_tmp_name = $this->{$this->file_column}['tmpname'][$this->{$this->file_column}];
      $new_name = $this->{$this->file_column}['name'][$this->{$this->file_column}];
      $this->{$this->type_column} = $this->{$this->file_column}['type'][$this->{$this->file_column}];
      if(move_uploaded_file($up_tmp_name, WAX_ROOT.$this->file_base.File::safe_file_save($this->file_base, $new_name)) ) {
 			  chmod($path.$filename, 0777);
 			  $this->{$this->file_column}=$new_name;
      }
    }
  }
    
  protected function resize_image() {
    $original = $this->file_base.$this->{$this->path_column}.$this->{$this->file_column};
    if($this->max_image_size >0 && File::is_image($original)) {
  		File::resize_image($original, $original, $this->max_image_size, true);
  		return true;
  	} else return false;
  }
    
  protected function write_thumbs() {
    $original = $this->file_base.$this->{$this->path_column}.$this->{$this->file_column};
  	if(!$this->write_image_thumbs) {
  	  return false;
	  }
  	$thumb_dir = PUBLIC_DIR.$this->thumb_base.$this->id."/";
  	if(!is_dir($thumb_dir)) mkdir($thumb_dir, 0777);
  	foreach($this->create_thumbs as $thumb=>$size) {
  		if(!is_dir($thumb_dir.$thumb)) mkdir($thumb_dir.$thumb, 0777);
  		$destination = $thumb_dir.$thumb."/".$this->{$this->file_column};
  		if(File::is_image($original)) File::resize_image($original, $destination, $size);
  	}
  	return true;
  }
  
  protected function clear_thumbs() {
		if(File::recursively_delete($this->thumb_base.$this->id) ) return true;
		return false;
	}

} 

?>