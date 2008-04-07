<?php

/**
 * WaxModelFields class
 *
 * @package PHP-Wax
 **/
class FileField extends WaxModelField {
  
	//default name
	public $col_name = "filename";
  public $maxlength = "255";
	//extra file values
	public $file_root = "public/files/";
	public $url_root = "files/";

  public function setup() {	
		$this->create_directory(WAX_ROOT.$this->file_root);
		parent::setup();
	}

  public function validate() {
 	  $this->valid_required();
  }
	/**** overides *****/
	//before run the sync function, add the extra_db_fields
	public function before_sync() {}  
	
	//save function needs to handle the post upload of a single file
	public function save() {
		//file is present and has a valid size
		if(isset($_FILES[$this->model->table]['name'][$this->col_name]) && ($_FILES[$this->model->table]['size'][$this->col_name] > 0) ){
			//save file to hdd & change col_name value to new_path
			$column = $this->col_name;
			$path = $this->save_file($_FILES[$this->model->table]);
			if($path) $this->model->$column = $path;
			parent::save();
		}
		
	}
	
	public function get(){
		return $this;
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
	
	private function save_file($file){
		$up_tmp_name = $file['tmp_name'][$this->col_name];
		$file_destination = WAX_ROOT.$this->file_root.File::safe_file_save(WAX_ROOT.$this->file_root,$file['name'][$this->col_name]);
		//if(move_uploaded_file($up_tmp_name, $file_destination) ) chmod($file_destination, 0777);
		if(rename($up_tmp_name, $file_destination) ) chmod($file_destination, 0777);
		else return false;
		
		return $file_destination;
	}

	//url path
	public function url(){
		$column = $this->col_name;
		return "/".str_replace(WAX_ROOT.$this->file_root, $this->url_root, $this->model->$column);
	}
	//file path
	public function path(){
		$column = $this->col_name;
		return $this->model->$column;
	}
} 
