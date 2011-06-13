<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class FileField extends WaxModelField {
  
	//default name
	public $col_name;
	//extra file values
	public $file_root = "public/files/";
	public $url_root = "files/";
	//allowed extensions - array of exts, false means everythings allowed
	public $allowed_extensions = false;
	public $widget = "FileInput";
	public $messages = array(
    "format"=>      "%s is not a valid format",
    "size_large"  =>      "image uploaded is too large (%s or less)",
    "size_small"  =>      "%s is too small"
  );
	public $data_type = "string";
	
	public $max_size = false;
	public $min_size = false;

	public function __construct($column, $model, $options = array()) {	
    if(isset($options['allowed_extensions'])){
			if(is_array($options['allowed_extensions']) ){
				foreach($options['allowed_extensions'] as $index=> $extension) $this->allowed_extensions[] = $extension; 
			} else $this->allowed_extensions[] = $options['allowed_extensions'];
			unset($options['allowed_extensions']);
		}
		$this->col_name = $column;
		parent::__construct($column, $model, $options);
  }

  public function setup() {	
		$this->create_directory(WAX_ROOT.$this->file_root);
		parent::setup();
	}

  public function validate() {
		$this->valid_extension();
		$this->valid_size();
  }
	public function valid_extension(){
		$file = $_FILES[$this->model->table];
		$name= $file['name'][$this->col_name];
		if(strlen($name)){
			$ext = strtolower(substr($name, strrpos($name, ".") ));
			if($this->allowed_extensions && !in_array($ext, $this->allowed_extensions)  ) $this->add_error($this->field, sprintf($this->messages["format"], $ext));
		}
	}
	
	public function valid_size() {
	  $file = $_FILES[$this->model->table];
		$error= $file['error'][$this->col_name];
		if($error == 2) $this->add_error($this->field, sprintf($this->messages["size_large"], $this->max_size / 1024 . "kb"));
		//if($this->min_size && $size < $this->min_size) $this->add_error($this->field, sprintf($this->messages["size"], $size));
	}
	/**** overides *****/

	public function get(){
		$ret = array("filename"=>$this->filename(), "path"=>$this->path(), "extension"=>$this->extension(), "url"=>$this->url() );
		return $ret;
	}
	
	public function __toString() {
		$file_info = $this->get();
		return $file_info["filename"];
	}
	//save function needs to handle the post upload of a single file
	public function save() {
		//file is present and has a valid size
		if(is_string($_FILES[$this->model->table]['name'][$this->col_name]) && ($_FILES[$this->model->table]['size'][$this->col_name] > 0) ){
			//save file to hdd & change col_name value to new_path
			$column = $this->col_name;
			$path = $this->save_file($_FILES[$this->model->table]['tmp_name'][$this->col_name], $_FILES[$this->model->table]['name'][$this->col_name]);
			if($path) $this->model->$column = $path;
			//prevent resaves for things like join models
			unset($_FILES[$this->model->table]['size'][$this->col_name]);				
		}
		
	}
	

	
	/**** EXTRAS *****/
	private function create_directory($dir){
		if(is_dir($dir)) return true;
		if(substr_count($dir, WAX_ROOT)>0){
			$new_dir = str_replace(WAX_ROOT, "", $dir);
			$path = explode("/", $new_dir);
			$dirname = WAX_ROOT;
			foreach($path as $depth => $name){
				$dirname .= $name . "/";
				if(!is_dir($dirname) ) mkdir($dirname);
			}
			return true;
		} else return false;
	}
	
	public function save_file($up_tmp_name, $file){
		$file_destination = WAX_ROOT.$this->file_root.File::safe_file_save(WAX_ROOT.$this->file_root,$file);
		//if(move_uploaded_file($up_tmp_name, $file_destination) ) chmod($file_destination, 0777);
		if(rename($up_tmp_name, $file_destination) ) chmod($file_destination, 0777);
		else return false;
		
		return $file_destination;
	}

	//url path
	public function url(){
		$path = $this->path();
		return "/".str_replace(WAX_ROOT.$this->file_root, $this->url_root, $path);
	}
	//file path
	public function path(){
		$column = $this->col_name;
		return $this->model->row[$column];
	}
	public function filename(){
		$path = $this->path();
		return substr($path, strrpos($path, "/")+1 );
	}
	public function extension(){
		$path = $this->path();
		return substr($path, strrpos($path, "."));
	}
} 
